<?php

namespace Tests\Feature;

use App\Core\Services\BlockLibrary;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The page builder is the CMS's highest-traffic feature. These tests assert
 * the whole loop: build a structure in the admin, save it, and see real HTML
 * on the public site — not just that the endpoints return 200.
 */
class PageBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $user = User::create([
            'name' => 'B', 'email' => 'b@b.local',
            'password' => Hash::make('password123'),
            'status' => 'active', 'is_active' => true,
        ]);
        $user->roles()->attach(\App\Models\Role::where('slug', 'admin')->first());

        return $user;
    }

    public function test_every_catalog_component_renders_without_error(): void
    {
        // Each component is dropped in with its declared defaults. A component
        // that throws here is one a page builder user would hit immediately.
        foreach (BlockLibrary::catalog() as $component) {
            $builder = [
                'sections' => [[
                    'name' => 'test',
                    'blocks' => [array_merge(['type' => $component['type']], $component['defaults'])],
                ]],
            ];

            $html = BlockLibrary::render($builder);

            $this->assertIsString($html, $component['type']);
            $this->assertNotSame('', trim($html), 'Component '.$component['type'].' rendered nothing');
        }
    }

    public function test_heading_and_button_render_real_markup(): void
    {
        $html = BlockLibrary::render([
            'sections' => [[
                'blocks' => [
                    ['type' => 'heading', 'heading' => 'Our Services', 'level' => 'h2'],
                    ['type' => 'button', 'heading' => 'Contact us', 'link' => '/contact', 'style' => 'primary'],
                ],
            ]],
        ]);

        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('Our Services', $html);
        $this->assertStringContainsString('href="/contact"', $html);
        $this->assertStringContainsString('Contact us', $html);
    }

    public function test_builder_payload_survives_save_and_renders_publicly(): void
    {
        $user = $this->admin();

        $builder = [
            'sections' => [
                [
                    'name' => 'Hero',
                    'layout' => 'hero',
                    'background' => '#0f172a',
                    'blocks' => [
                        ['type' => 'heading', 'heading' => 'Built to sell', 'level' => 'h1'],
                        ['type' => 'text', 'text' => 'A company website you run yourself.'],
                        ['type' => 'button', 'heading' => 'Get started', 'link' => '/contact'],
                    ],
                ],
                [
                    'name' => 'Features',
                    'blocks' => [
                        ['type' => 'heading', 'heading' => 'What you get'],
                        ['type' => 'accordion', 'items' => ["Fast|Loads quickly", "Safe|Built on Laravel"]],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user)->post('/admin/cms/pages', [
            'title' => 'Builder round trip',
            'status' => 'published',
            'builder' => json_encode($builder),
        ]);

        $response->assertRedirect(route('admin.cms.pages.index'));
        $response->assertSessionHasNoErrors();

        // The structure was stored as real JSON, not stringified nonsense.
        $page = Page::where('title', 'Builder round trip')->firstOrFail();
        $this->assertSame('published', $page->status);
        $this->assertCount(2, $page->builder['sections']);
        $this->assertCount(3, $page->builder['sections'][0]['blocks']);
        $this->assertSame('Built to sell', $page->builder['sections'][0]['blocks'][0]['heading']);

        // And the public page renders it.
        $this->get('/p/'.$page->slug)
            ->assertOk()
            ->assertSee('Built to sell')
            ->assertSee('A company website you run yourself.')
            ->assertSee('What you get')
            ->assertSee('Loads quickly', false);
    }

    public function test_saving_a_page_takes_a_revision_snapshot(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post('/admin/cms/pages', [
            'title' => 'Versioned', 'status' => 'published', 'builder' => json_encode(['sections' => []]),
        ]);
        $page = Page::where('title', 'Versioned')->firstOrFail();

        $this->actingAs($user)->post('/admin/cms/pages/'.$page->id, [
            'title' => 'Versioned v2', 'status' => 'published', 'builder' => json_encode(['sections' => []]),
        ]);

        $this->assertDatabaseHas('page_revisions', ['page_id' => $page->id]);
    }

    public function test_invalid_builder_json_is_rejected_with_a_message(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post('/admin/cms/pages', [
                'title' => 'Broken', 'status' => 'draft', 'builder' => '{not json',
            ])
            ->assertSessionHasErrors('builder');

        $this->assertDatabaseMissing('pages', ['title' => 'Broken']);
    }

    public function test_draft_pages_are_not_publicly_visible(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post('/admin/cms/pages', [
            'title' => 'Hidden draft', 'status' => 'draft',
            'builder' => json_encode(['sections' => []]),
        ]);

        $page = Page::where('title', 'Hidden draft')->firstOrFail();
        $this->get('/p/'.$page->slug)->assertNotFound();
    }

    public function test_section_visibility_is_persisted_per_breakpoint(): void
    {
        $user = $this->admin();

        $builder = ['sections' => [[
            'name' => 'Mobile only',
            'hide_desktop' => true,
            'hide_mobile' => false,
            'blocks' => [['type' => 'heading', 'heading' => 'See on mobile']],
        ]]];

        $this->actingAs($user)->post('/admin/cms/pages', [
            'title' => 'Responsive', 'status' => 'published', 'builder' => json_encode($builder),
        ]);

        $page = Page::where('title', 'Responsive')->firstOrFail();
        $this->assertTrue($page->builder['sections'][0]['hide_desktop']);
    }

    public function test_saved_blocks_can_be_reused_across_pages(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post('/admin/cms/blocks', [
            'name' => 'Promo CTA',
            'type' => 'button',
            'data' => json_encode(['heading' => 'Buy now', 'link' => '/pricing']),
            'is_global' => '1',
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reusable_blocks', ['name' => 'Promo CTA']);

        $block = \App\Models\ReusableBlock::where('name', 'Promo CTA')->firstOrFail();
        $builder = ['sections' => [['blocks' => [array_merge(['type' => $block->type], $block->data)]]]];

        $this->get(route('admin.cms.blocks.index'))->assertOk()->assertSee('Promo CTA');
        $this->assertStringContainsString('Buy now', BlockLibrary::render($builder));
    }
}
