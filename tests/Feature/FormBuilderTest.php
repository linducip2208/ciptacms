<?php

namespace Tests\Feature;

use App\Core\Services\FormRenderer;
use App\Models\Form;
use App\Models\FormField;
use App\Models\FormSubmission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The form builder is a drag-and-drop shell on top of three real endpoints
 * (fields.store/update/destroy, fields.reorder) and one service
 * (FormRenderer) that both previews and validates. These tests drive the same
 * requests the UI makes and then read the database, so a builder that renders
 * beautifully but does not persist anything still fails here.
 *
 * KNOWN BACKEND DEFECTS (files outside this task's ownership, so left
 * untouched). Two tests at the bottom of this file fail because of them and
 * are the specification for the fix:
 *
 *   1. CmsController::saveField() is declared
 *      `saveField(Request $r, Form $form, $field = null)`. Only the typed
 *      parameter is route-model-bound, so on the update route $field arrives
 *      as the raw id STRING and `$row = $field ?? new FormField` hands a
 *      string to `fill()`. Every field update from the inspector 500s.
 *      Fix: type the parameter (FormField|null) and resolve the model.
 *   2. App\Models\FormSubmission has no form() relationship, so
 *      CmsController::submissions() -> `FormSubmission::with('form')` throws
 *      RelationNotFoundException and the submissions list 500s.
 */
class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);

        $this->admin = User::create([
            'name' => 'Builder', 'email' => 'builder@builder.local',
            'password' => Hash::make('password123'),
            'status' => 'active', 'is_active' => true,
        ]);
        $this->admin->roles()->attach(Role::where('slug', 'admin')->firstOrFail());
    }

    protected function form(array $attrs = []): Form
    {
        return Form::create(array_merge([
            'name' => 'Contact us',
            'slug' => 'contact-'.bin2hex(random_bytes(2)),
            'description' => 'Tell us what you need.',
        ], $attrs));
    }

    protected function fieldsUrl(Form $form): string
    {
        return "/admin/cms/forms/{$form->id}/fields";
    }

    /**
     * The payload the builder's canvas and inspector always send: every key
     * the store endpoint validates, because it reads them without a `??`.
     */
    protected function fieldPayload(array $attrs = []): array
    {
        return array_merge([
            'label' => '',
            'name' => '',
            'type' => 'text',
            'placeholder' => '',
            'help' => '',
            'options' => '',
            'is_required' => 0,
            'is_unique' => 0,
            'is_active' => 1,
        ], $attrs);
    }

    protected function addField(Form $form, array $attrs = [], string $context = ''): FormField
    {
        $before = FormField::where('form_id', $form->id)->count();

        $this->actingAs($this->admin)
            ->post($this->fieldsUrl($form), $this->fieldPayload($attrs))
            ->assertSessionHasNoErrors();

        // Form::fields() is pre-ordered by sort_order, so ask for the new row
        // directly rather than through the relation.
        $field = FormField::where('form_id', $form->id)->orderByDesc('id')->first();
        $this->assertNotNull(
            $field,
            'no field was created '.($context ? 'for '.$context : '')
        );
        $this->assertSame($before + 1, FormField::where('form_id', $form->id)->count());

        return $field;
    }


    // ==================================================================
    // The builder page itself
    // ==================================================================

    public function test_builder_page_renders_palette_canvas_and_inspector(): void
    {
        $form = $this->form();
        $this->addField($form, ['label' => 'Your name', 'name' => 'your_name', 'type' => 'text']);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.cms.forms.builder', $form))
            ->assertOk()
            ->getContent();

        // The palette is generated from FormField::TYPES, so every declared
        // type has to be offered, grouped and labelled.
        foreach (FormField::TYPES as $type => $meta) {
            $this->assertStringContainsString('"type":"'.$type.'"', $html, $type.' missing from the palette');
        }
        $this->assertStringContainsString('"label":"'.$meta['label'].'"', $html);
        $this->assertStringContainsString('Long text', $html);
        $this->assertStringContainsString('File upload', $html);

        // The three working areas exist, and the live endpoints are wired in
        // (@json escapes the slashes, so compare against the encoded form).
        $this->assertStringContainsString('linduFormBuilder()', $html);
        $this->assertStringContainsString('Canvas', $html);
        $this->assertStringContainsString('Inspector', $html);
        $this->assertStringContainsString('Live preview', $html);
        $this->assertStringContainsString(
            json_encode(route('admin.cms.forms.fields.reorder', $form)),
            $html,
            'the reorder endpoint is not wired into the client'
        );
        $this->assertStringContainsString(
            json_encode(route('admin.cms.forms.fields.store', $form)),
            $html,
            'the field-create endpoint is not wired into the client'
        );
        $this->assertStringContainsString(
            json_encode(route('admin.cms.forms.fields.update', ['form' => $form, 'field' => '__ID__'])),
            $html,
            'the field-update endpoint is not wired into the client'
        );
        $this->assertStringContainsString(
            json_encode(route('admin.cms.forms.fields.destroy', ['form' => $form, 'field' => '__ID__'])),
            $html,
            'the field-delete endpoint is not wired into the client'
        );
        $this->assertStringContainsString(
            route('admin.cms.forms.update', $form),
            $html,
            'the form-settings form does not post to the update route'
        );
        $this->assertStringContainsString(
            route('admin.cms.forms.destroy', $form),
            $html,
            'the delete-form form does not post to the destroy route'
        );

        // The existing field is seeded into the client state, not just listed.
        $this->assertStringContainsString('"name":"your_name"', $html);

        // No dead links inside the builder markup itself.
        $builder = substr($html, (int) strpos($html, 'linduFormBuilder()'));
        $this->assertStringNotContainsString('href="#"', $builder);
        $this->assertStringNotContainsString("href='#'", $builder);
    }

    public function test_builder_page_renders_for_a_form_with_no_fields(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.cms.forms.builder', $this->form()))
            ->assertOk()
            ->assertSee('This form has no fields yet.')
            ->assertSee('Add the first field');
    }

    public function test_builder_page_carries_the_form_settings_for_the_settings_form(): void
    {
        $form = $this->form(['name' => 'Quote request', 'slug' => 'quote-request']);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.cms.forms.builder', $form))
            ->assertOk()
            ->getContent();

        foreach (['form-title', 'form-slug', 'form-description', 'form-submit-label', 'form-success', 'form-status'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html, $id.' is missing from the settings form');
        }
        $this->assertStringContainsString('value="Quote request"', $html);
        $this->assertStringContainsString('value="quote-request"', $html);
        $this->assertStringContainsString('Tell us what you need.', $html);
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
    }

    public function test_guests_cannot_reach_the_builder(): void
    {
        $form = $this->form();

        $this->get(route('admin.cms.forms.builder', $form))->assertRedirect(route('login'));
        $this->post($this->fieldsUrl($form), $this->fieldPayload(['label' => 'X', 'type' => 'text']))
            ->assertRedirect(route('login'));
        $this->assertCount(0, FormField::where('form_id', $form->id)->get());
    }

    public function test_the_forms_list_links_to_the_builder_and_offers_a_create_form(): void
    {
        $form = $this->form(['name' => 'Newsletter signup']);
        $this->addField($form, ['label' => 'Email', 'name' => 'email', 'type' => 'email']);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.cms.forms.index'))
            ->assertOk()
            ->assertSee('Newsletter signup')
            ->assertSee($form->slug)
            ->getContent();

        $this->assertStringContainsString(route('admin.cms.forms.builder', $form), $html);
        $this->assertStringContainsString(route('admin.cms.forms.store'), $html);

        foreach (['new-title', 'new-slug', 'new-description', 'new-submit-label', 'new-success', 'new-status'] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html, $id.' is missing from the create form');
        }
    }

    // ==================================================================
    // Field CRUD — the operations the canvas performs
    // ==================================================================

    public function test_every_palette_field_type_can_be_added_and_persists(): void
    {
        $form = $this->form();

        foreach (array_keys(FormField::TYPES) as $type) {
            $field = $this->addField($form, [
                'label' => ucfirst(str_replace('-', ' ', $type)).' value',
                'name' => $type.'_value',
                'type' => $type,
                'options' => in_array($type, FormField::OPTION_TYPES, true) ? "One\nTwo" : '',
            ], $type);

            $this->assertSame($type, $field->type, $type.' did not persist');
            $this->assertDatabaseHas('form_fields', [
                'id' => $field->id, 'form_id' => $form->id, 'type' => $type,
            ]);
        }

        $this->assertCount(count(FormField::TYPES), FormField::where('form_id', $form->id)->get());

        // And they are all on the canvas after a reload.
        $html = $this->actingAs($this->admin)->get(route('admin.cms.forms.builder', $form))->getContent();
        foreach (array_keys(FormField::TYPES) as $type) {
            $this->assertStringContainsString('"type":"'.$type.'"', $html);
        }
    }

    public function test_a_new_field_is_appended_after_the_existing_ones(): void
    {
        $form = $this->form();
        $first = $this->addField($form, ['label' => 'First', 'name' => 'first', 'type' => 'text']);
        $second = $this->addField($form, ['label' => 'Second', 'name' => 'second', 'type' => 'text']);

        $this->assertGreaterThan($first->sort_order, $second->sort_order);
        $this->assertSame(['first', 'second'], $form->fresh()->fields->pluck('name')->all());
    }

    public function test_a_field_without_a_name_still_gets_a_usable_one(): void
    {
        $form = $this->form();
        $field = $this->addField($form, ['label' => 'Company Name', 'name' => '', 'type' => 'text']);

        $derived = $field->fresh()->name;
        $this->assertNotSame('', $derived, 'a blank name must fall back to something derived from the label');
        $this->assertSame(strtolower($derived), $derived, 'the derived name must be a safe lowercase key');
    }

    public function test_option_fields_round_trip_their_options(): void
    {
        $form = $this->form();

        // One value per line…
        $select = $this->addField($form, [
            'label' => 'Colour', 'name' => 'colour', 'type' => 'select',
            'options' => "Red\nGreen\nBlue",
        ]);
        $this->assertSame(['Red', 'Green', 'Blue'], $select->fresh()->options);

        // …and as a JSON map of value => label.
        $radio = $this->addField($form, [
            'label' => 'Plan', 'name' => 'plan', 'type' => 'radio',
            'options' => json_encode(['basic' => 'Basic plan', 'pro' => 'Pro plan']),
        ]);
        $this->assertSame(['basic' => 'Basic plan', 'pro' => 'Pro plan'], $radio->fresh()->options);

        // The values are what the renderer offers the visitor, which is the
        // same list the inspector's options textarea round-trips.
        $html = app(FormRenderer::class)->render($form->fresh());
        $this->assertStringContainsString('<option value="Green">Green</option>', $html);
        $this->assertStringContainsString('value="pro"', $html);
        $this->assertStringContainsString('Pro plan', $html);

        // Survives a builder reload: the textarea is pre-filled with the text
        // that CmsController::toList() understands. @json() emits with
        // JSON_HEX_QUOT, so build the expectation the same way.
        $builder = $this->actingAs($this->admin)->get(route('admin.cms.forms.builder', $form))->getContent();
        $this->assertStringContainsString(
            '"options_text":'.json_encode("Red\nGreen\nBlue", 15),
            $builder
        );
        $this->assertStringContainsString(
            '"options_text":'.json_encode('{"basic":"Basic plan","pro":"Pro plan"}', 15),
            $builder
        );
    }

    public function test_flags_placeholder_and_help_persist_on_creation(): void
    {
        $form = $this->form();
        $field = $this->addField($form, [
            'label' => 'Work email', 'name' => 'work_email', 'type' => 'email',
            'placeholder' => 'you@company.com', 'help' => 'We never share it.',
            'is_required' => 1, 'is_unique' => 1, 'is_active' => 1,
        ]);

        $fresh = $field->fresh();
        $this->assertSame('you@company.com', $fresh->placeholder);
        $this->assertSame('We never share it.', $fresh->help);
        $this->assertTrue($fresh->is_required);
        $this->assertTrue($fresh->is_unique);
        $this->assertTrue($fresh->is_active);
    }

    public function test_an_inactive_field_is_stored_and_hidden_from_the_public_form(): void
    {
        $form = $this->form();
        $field = $this->addField($form, [
            'label' => 'Retired', 'name' => 'retired', 'type' => 'text', 'is_active' => 0,
        ]);

        $this->assertFalse($field->fresh()->is_active);
        $this->assertStringNotContainsString('name="retired"', app(FormRenderer::class)->render($form->fresh()));
    }

    public function test_a_field_type_outside_the_palette_is_rejected(): void
    {
        $form = $this->form();

        $this->actingAs($this->admin)
            ->post($this->fieldsUrl($form), $this->fieldPayload(['label' => 'Nope', 'type' => 'wormhole']))
            ->assertSessionHasErrors('type');

        $this->assertCount(0, FormField::where('form_id', $form->id)->get());
    }

    public function test_a_field_without_a_label_is_rejected(): void
    {
        $form = $this->form();

        $this->actingAs($this->admin)
            ->post($this->fieldsUrl($form), $this->fieldPayload(['label' => '', 'type' => 'text']))
            ->assertSessionHasErrors('label');

        $this->assertCount(0, FormField::where('form_id', $form->id)->get());
    }

    public function test_deleting_a_field_removes_it(): void
    {
        $form = $this->form();
        $keep = $this->addField($form, ['label' => 'Keep', 'name' => 'keep', 'type' => 'text']);
        $drop = $this->addField($form, ['label' => 'Drop', 'name' => 'drop', 'type' => 'text']);

        $this->actingAs($this->admin)
            ->delete(route('admin.cms.forms.fields.destroy', [$form, $drop]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('form_fields', ['id' => $drop->id]);
        $this->assertDatabaseHas('form_fields', ['id' => $keep->id]);
        $this->assertSame(['keep'], $form->fresh()->fields->pluck('name')->all());
    }

    public function test_a_field_from_another_form_cannot_be_deleted(): void
    {
        $mine = $this->form();
        $theirs = $this->form();
        $foreign = $this->addField($theirs, ['label' => 'Theirs', 'name' => 'theirs', 'type' => 'text']);

        $this->actingAs($this->admin)
            ->delete(route('admin.cms.forms.fields.destroy', [$mine, $foreign]))
            ->assertNotFound();

        $this->assertDatabaseHas('form_fields', ['id' => $foreign->id]);
    }

    // ==================================================================
    // Ordering
    // ==================================================================

    public function test_reordering_persists(): void
    {
        $form = $this->form();
        $a = $this->addField($form, ['label' => 'A', 'name' => 'a', 'type' => 'text']);
        $b = $this->addField($form, ['label' => 'B', 'name' => 'b', 'type' => 'text']);
        $c = $this->addField($form, ['label' => 'C', 'name' => 'c', 'type' => 'text']);

        $this->assertSame(['a', 'b', 'c'], $form->fresh()->fields->pluck('name')->all());

        $response = $this->actingAs($this->admin)
            ->post(route('admin.cms.forms.fields.reorder', $form), ['order' => [$c->id, $a->id, $b->id]])
            ->assertOk();

        $this->assertTrue($response->json('ok'));

        $this->assertSame(0, $c->fresh()->sort_order);
        $this->assertSame(1, $a->fresh()->sort_order);
        $this->assertSame(2, $b->fresh()->sort_order);
        $this->assertSame(['c', 'a', 'b'], $form->fresh()->fields->pluck('name')->all());

        // And the builder hands the canvas back in that order.
        $this->actingAs($this->admin)
            ->get(route('admin.cms.forms.builder', $form))
            ->assertOk()
            ->assertSeeInOrder(['"name":"c"', '"name":"a"', '"name":"b"']);
    }

    public function test_reorder_ignores_fields_belonging_to_another_form(): void
    {
        $form = $this->form();
        $mine = $this->addField($form, ['label' => 'Mine', 'name' => 'mine', 'type' => 'text']);
        $other = $this->addField($this->form(), ['label' => 'Other', 'name' => 'other', 'type' => 'text']);
        $before = $other->fresh()->sort_order;

        $this->actingAs($this->admin)
            ->post(route('admin.cms.forms.fields.reorder', $form), ['order' => [$mine->id, $other->id]])
            ->assertOk();

        $this->assertSame($before, $other->fresh()->sort_order, 'a foreign field must not be reordered');
    }

    // ==================================================================
    // The rendered form
    // ==================================================================

    public function test_rendered_form_reflects_required_placeholder_help_and_options(): void
    {
        $form = $this->form();
        $this->addField($form, [
            'label' => 'Your name', 'name' => 'full_name', 'type' => 'text',
            'placeholder' => 'Jane Doe', 'help' => 'As it appears on your ID.', 'is_required' => '1',
        ]);
        $this->addField($form, [
            'label' => 'Colour', 'name' => 'colour', 'type' => 'radio',
            'options' => json_encode(['red' => 'Red', 'green' => 'Green']),
        ]);
        $this->addField($form, ['label' => 'Retired', 'name' => 'retired', 'type' => 'text', 'is_active' => 0]);

        $html = app(FormRenderer::class)->render($form->fresh());

        $this->assertStringContainsString('name="full_name"', $html);
        $this->assertStringContainsString('placeholder="Jane Doe"', $html);
        $this->assertStringContainsString('As it appears on your ID.', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('Tell us what you need.', $html, 'the form description is shown');
        $this->assertStringContainsString('type="radio"', $html);
        $this->assertStringContainsString('value="green"', $html, 'a keyed option uses its key as the value');
        $this->assertStringContainsString('<span>Green</span>', $html, 'a keyed option shows its label');
        $this->assertStringNotContainsString('name="retired"', $html, 'inactive fields stay off the public form');
    }

    public function test_the_rendered_form_carries_csrf_honeypot_and_timestamp(): void
    {
        $form = $this->form();
        $this->addField($form, ['label' => 'Email', 'name' => 'email', 'type' => 'email']);

        $html = app(FormRenderer::class)->render($form->fresh());

        $this->assertStringContainsString('name="website"', $html, 'honeypot input');
        $this->assertStringContainsString('name="_t"', $html, 'minimum fill time stamp');
        $this->assertStringContainsString('name="_token"', $html, 'csrf field');
        $this->assertStringContainsString('/api/v1/forms/'.$form->slug, $html);
    }

    public function test_the_builder_embeds_the_server_rendered_form(): void
    {
        $form = $this->form();
        $this->addField($form, ['label' => 'Email', 'name' => 'work_email', 'type' => 'email', 'is_required' => 1]);

        $this->actingAs($this->admin)
            ->get(route('admin.cms.forms.builder', $form))
            ->assertOk()
            ->assertSee('name=&quot;work_email&quot;', false)
            ->assertSee('srcdoc="&lt;form', false);
    }

    // ==================================================================
    // Submissions
    // ==================================================================

    public function test_a_valid_submission_is_stored_and_listed_in_the_admin(): void
    {
        $form = $this->form();
        $this->addField($form, ['label' => 'Name', 'name' => 'full_name', 'type' => 'text', 'is_required' => '1']);
        $this->addField($form, ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'is_required' => '1']);
        $this->addField($form, [
            'label' => 'Interests', 'name' => 'interests', 'type' => 'multiselect',
            'options' => "Design\nDevelopment",
        ]);

        $result = app(FormRenderer::class)->submit($form->fresh(), [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'interests' => ['Design'],
        ], '10.0.0.9', 'phpunit');

        $this->assertTrue($result['ok'], json_encode($result['errors'] ?? $result['message'] ?? []));

        $stored = FormSubmission::where('form_id', $form->id)->firstOrFail();
        $this->assertSame('Jane Doe', $stored->data['full_name']);
        $this->assertSame('jane@example.com', $stored->data['email']);
        $this->assertSame(['Design'], $stored->data['interests']);
        $this->assertSame('10.0.0.9', $stored->ip);

        // The operator sees it on the builder's own submissions card…
        $this->actingAs($this->admin)
            ->get(route('admin.cms.forms.builder', $form->fresh()))
            ->assertOk()
            ->assertSee('1 total')
            ->assertSee('Jane Doe');
    }

    public function test_a_submission_missing_a_required_field_is_rejected(): void
    {
        $form = $this->form();
        $this->addField($form, ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'is_required' => '1']);
        $this->addField($form, ['label' => 'Phone', 'name' => 'phone', 'type' => 'phone', 'is_required' => '1']);

        $result = app(FormRenderer::class)->submit($form->fresh(), ['phone' => '0812'], '10.0.0.10');

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertCount(0, FormSubmission::where('form_id', $form->id)->get());
    }

    public function test_a_submission_with_a_malformed_email_is_rejected(): void
    {
        $form = $this->form();
        $this->addField($form, ['label' => 'Email', 'name' => 'email', 'type' => 'email']);

        $result = app(FormRenderer::class)->submit($form->fresh(), ['email' => 'not-an-email'], '10.0.0.11');

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertCount(0, FormSubmission::where('form_id', $form->id)->get());
    }

    public function test_the_honeypot_rejects_a_bot_submission(): void
    {
        $form = $this->form();
        $this->addField($form, ['label' => 'Email', 'name' => 'email', 'type' => 'email']);

        $result = app(FormRenderer::class)->submit($form->fresh(), [
            'email' => 'bot@example.com',
            'website' => 'http://spam.example',
        ], '10.0.0.12');

        $this->assertFalse($result['ok']);
        $this->assertSame('Your submission could not be processed.', $result['message']);
        $this->assertCount(0, FormSubmission::where('form_id', $form->id)->get());
    }

    public function test_deleting_a_form_that_has_submissions_is_refused(): void
    {
        $form = $this->form();
        $this->addField($form, ['label' => 'Email', 'name' => 'email', 'type' => 'email']);
        app(FormRenderer::class)->submit($form->fresh(), ['email' => 'a@b.co'], '10.0.0.13');

        $this->actingAs($this->admin)
            ->delete(route('admin.cms.forms.destroy', $form))
            ->assertSessionHasErrors('msg');

        $this->assertDatabaseHas('forms', ['id' => $form->id]);
    }

    public function test_deleting_an_empty_form_removes_it_with_its_fields(): void
    {
        $form = $this->form(['name' => 'Disposable']);
        $this->addField($form, ['label' => 'Email', 'name' => 'email', 'type' => 'email']);

        $this->actingAs($this->admin)
            ->delete(route('admin.cms.forms.destroy', $form))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.cms.forms.index'));

        $this->assertDatabaseMissing('forms', ['id' => $form->id]);
        $this->assertCount(0, FormField::where('form_id', $form->id)->get());
    }

    // ==================================================================
    // Regressions for backend defects outside this task's file ownership
    // ==================================================================

    /**
     * The forms table has no title / submit_label / success_message / status
     * columns and CmsController::saveForm() fills all of them, so the create
     * and update endpoints cannot store a form. The builder's settings card
     * posts the documented payload; this test is the spec for the fix.
     */
    public function test_a_form_can_be_created_and_its_settings_saved(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.cms.forms.store'), [
                'title' => 'Quote request',
                'slug' => 'quote-request',
                'description' => 'Tell us about the job.',
                'submit_label' => 'Send it',
                'success_message' => 'We will be in touch.',
                'status' => 'published',
            ])
            ->assertSessionHasNoErrors();

        $form = Form::where('slug', 'quote-request')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.cms.forms.builder', $form))
            ->assertOk()
            ->assertSee('Tell us about the job.')
            ->assertSee('Send it');

        $this->actingAs($this->admin)
            ->put(route('admin.cms.forms.update', $form), [
                'title' => 'Quote request v2',
                'slug' => 'quote-request',
                'description' => 'Updated copy.',
                'submit_label' => 'Send',
                'status' => 'draft',
            ])
            ->assertSessionHasNoErrors();

        $this->assertStringContainsString('Updated copy.', (string) $form->fresh()->description);
    }

    /**
     * CmsController::saveField() receives the untyped $field route parameter as
     * a string; the update path of the inspector depends on the model binding.
     */
    public function test_the_inspector_update_endpoint_persists_every_property(): void
    {
        $form = $this->form();
        $field = $this->addField($form, ['label' => 'Email', 'name' => 'email', 'type' => 'email']);

        $this->actingAs($this->admin)
            ->put(route('admin.cms.forms.fields.update', [$form, $field]), $this->fieldPayload([
                'label' => 'Work email',
                'name' => 'work_email',
                'type' => 'email',
                'placeholder' => 'you@company.com',
                'help' => 'We never share it.',
                'is_required' => '1',
                'is_unique' => '1',
                'is_active' => '1',
            ]))
            ->assertSessionHasNoErrors();

        $fresh = $field->fresh();
        $this->assertSame('Work email', $fresh->label);
        $this->assertSame('work_email', $fresh->name);
        $this->assertSame('you@company.com', $fresh->placeholder);
        $this->assertSame('We never share it.', $fresh->help);
        $this->assertTrue($fresh->is_required);
        $this->assertTrue($fresh->is_unique);
        $this->assertTrue($fresh->is_active);
    }

    /**
     * Fails today: App\Models\FormSubmission has no form() relationship, so
     * CmsController::submissions() -> FormSubmission::with('form') throws.
     */
    public function test_the_submissions_list_shows_a_stored_submission(): void
    {
        $form = $this->form();
        $this->addField($form, ['label' => 'Name', 'name' => 'full_name', 'type' => 'text']);
        app(FormRenderer::class)->submit($form->fresh(), ['full_name' => 'Listed Person'], '10.0.0.14');

        $this->actingAs($this->admin)
            ->get(route('admin.cms.submissions.index'))
            ->assertOk()
            ->assertSee('Listed Person');
    }
}
