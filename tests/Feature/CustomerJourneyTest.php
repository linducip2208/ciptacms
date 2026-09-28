<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\FrontendMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * What a customer actually does, in order, with no developer in the loop.
 *
 * Install, sign in, write a page, drop in a component, publish, receive a
 * form submission, upload media, reorder the menu, restyle the brand, check
 * SEO, and take a backup. If any step of that journey breaks, the product is
 * not sellable, however many unit tests pass.
 */
class CustomerJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function install(): void
    {
        Artisan::call('lindu:install');
        Artisan::call('db:seed', ['--force' => true]);

        $this->admin = User::create([
            'name' => 'Owner', 'email' => 'owner@acme.test',
            'password' => Hash::make('password123'),
            'status' => 'active', 'is_active' => true,
        ]);
    }

    protected function login(): void
    {
        $this->actingAs($this->admin);
    }

    // ---- 1. a clean install produces a working site --------------------

    public function test_a_fresh_install_yields_a_working_site(): void
    {
        $this->install();

        // Seeded roles, an administrator and the default brand exist.
        $this->assertDatabaseHas('roles', ['slug' => 'admin']);
        $this->assertNotNull(setting('general.site_name'));

        // Every public page a company site needs answers.
        foreach (['/', '/about', '/services', '/products', '/portfolio', '/team',
            '/testimonials', '/clients', '/faq', '/gallery', '/careers', '/blog', '/contact'] as $url) {
            $this->get($url)->assertOk("GET {$url} after a fresh install");
        }

        // Demo content is present and editable, not hard-coded into a template.
        $this->assertGreaterThan(0, \App\Models\Cp\Service::count());
        $this->assertGreaterThan(0, \App\Models\Cp\TeamMember::count());
    }

    public function test_the_admin_panel_opens_for_a_fresh_install(): void
    {
        $this->install();
        $this->login();

        foreach (['/', '/cms/pages', '/cms/posts', '/media', '/menus', '/settings',
            '/cms/forms', '/modules', '/plugins', '/themes', '/backups', '/health'] as $path) {
            $this->get('/admin'.$path)->assertOk("GET /admin{$path} as the seeded administrator");
        }
    }

    // ---- 2. write and publish a page, no developer --------------------

    public function test_an_owner_can_publish_a_page_and_see_it_publicly(): void
    {
        $this->install();
        $this->login();

        $this->post(route('admin.cms.pages.save'), [
            'title' => 'Our new service',
            'slug' => 'our-new-service',
            'status' => 'published',
            'builder' => json_encode(['sections' => [[
                'blocks' => [
                    ['type' => 'heading', 'heading' => 'Consulting', 'level' => 'h1'],
                    ['type' => 'text', 'text' => '<p>We help teams ship.</p>'],
                ],
            ]]]),
        ])->assertRedirect(route('admin.cms.pages.index'));

        $page = Page::where('slug', 'our-new-service')->firstOrFail();
        $this->assertSame('published', $page->status);

        $this->get('/p/our-new-service')
            ->assertOk()
            ->assertSee('Consulting')
            ->assertSee('We help teams ship');
    }

    public function test_a_draft_is_not_public(): void
    {
        $this->install();
        $this->login();

        $this->post(route('admin.cms.pages.save'), [
            'title' => 'Secret', 'slug' => 'secret', 'status' => 'draft',
            'builder' => json_encode(['sections' => []]),
        ]);

        $this->get('/p/secret')->assertNotFound();
    }

    // ---- 3. receive a form submission from a visitor ------------------

    public function test_a_visitor_submission_reaches_the_admin(): void
    {
        $this->install();

        $form = Form::create([
            'name' => 'Quote request', 'slug' => 'quote',
            'status' => 'published', 'submit_label' => 'Send',
            'success_message' => 'Received.', 'is_active' => true,
        ]);
        $form->fields()->create([
            'label' => 'Your name', 'name' => 'name', 'type' => 'text', 'is_required' => true, 'is_active' => true,
        ]);
        $form->fields()->create([
            'label' => 'Email', 'name' => 'email', 'type' => 'email', 'is_required' => true, 'is_active' => true,
        ]);

        $this->postJson("/api/v1/forms/{$form->slug}", [
            'name' => 'Ana', 'email' => 'ana@example.com',
        ])->assertStatus(201);

        $this->assertDatabaseHas('form_submissions', ['form_id' => $form->id]);

        $this->login();
        $this->get(route('admin.cms.submissions.index'))
            ->assertOk()
            ->assertSee('Ana');
    }

    public function test_a_required_field_is_enforced_for_the_visitor(): void
    {
        $this->install();

        $form = Form::create(['name' => 'Q', 'slug' => 'q2', 'status' => 'published', 'is_active' => true]);
        $form->fields()->create([
            'label' => 'Your name', 'name' => 'name', 'type' => 'text', 'is_required' => true, 'is_active' => true,
        ]);

        $this->postJson('/api/v1/forms/q2', ['email' => 'a@b.test'])->assertStatus(422);
        $this->assertDatabaseCount('form_submissions', 0);
    }

    // ---- 4. media ------------------------------------------------------

    public function test_an_owner_can_upload_media(): void
    {
        Storage::fake('public');
        $this->install();
        $this->login();

        $this->post(route('admin.media.store'), [
            'files' => [UploadedFile::fake()->image('logo.png', 40, 40)],
        ])->assertRedirect();

        $this->assertDatabaseHas('media_files', ['original_name' => 'logo.png']);
    }

    // ---- 5. navigation and branding -----------------------------------

    public function test_the_menu_drives_the_public_navigation(): void
    {
        $this->install();
        $this->login();

        MenuItem::create([
            'location' => 'primary', 'title' => 'Our Work',
            'url' => '/portfolio', 'sort_order' => 1, 'is_visible' => true,
        ]);

        $this->get('/')->assertOk()->assertSee('Our Work');
    }

    public function test_rebranding_reaches_the_public_site(): void
    {
        $this->install();

        app(\App\Core\Services\SettingService::class)->set('general.site_name', 'Acme Industrial', 'text', 'general');
        app(\App\Core\Services\SettingService::class)->set('branding.primary_color', '#ff6600', 'text', 'branding');
        MenuServiceFlush();

        $this->get('/')->assertOk()->assertSee('Acme Industrial');
    }

    // ---- 6. SEO --------------------------------------------------------

    public function test_seo_is_emitted_for_a_published_page(): void
    {
        $this->install();
        $this->login();

        $this->post(route('admin.cms.pages.save'), [
            'title' => 'Indexed page', 'slug' => 'indexed-page', 'status' => 'published',
            'meta_title' => 'Indexed page | Acme', 'meta_description' => 'A page that search engines can read.',
            'builder' => json_encode(['sections' => []]),
        ]);

        $html = $this->get('/p/indexed-page')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="description"', $html);
        $this->assertStringContainsString('A page that search engines can read', $html);
        $this->assertStringContainsString('rel="canonical"', $html);

        $this->get('/sitemap.xml')->assertOk()->assertSee('indexed-page');
    }

    // ---- 7. backup -----------------------------------------------------

    public function test_an_owner_can_take_a_backup(): void
    {
        $this->install();
        $this->login();

        $this->post(route('admin.backups.run'), ['type' => 'database'])->assertRedirect();

        $this->assertDatabaseHas('backups', ['status' => 'completed']);
    }

    // ---- 8. licensing does not block ordinary use ----------------------

    public function test_an_unlicensed_site_still_serves_pages(): void
    {
        $this->install();

        // No licence is installed here. The site must still work, or a
        // customer evaluating the product sees a lock screen.
        $this->get('/')->assertOk();
        $this->assertFalse(
            app(\App\Core\Services\LicenseService::class)->status()['licensed'],
            'this test assumes no licence is installed'
        );
    }
}

function MenuServiceFlush(): void
{
    \App\Core\Services\MenuService::forget();
}
