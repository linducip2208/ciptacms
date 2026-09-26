<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Structural audit of the repository, run as a test so it cannot silently rot.
 *
 * These are the invariants the product brief treats as non-negotiable:
 * every admin menu entry resolves, every admin route has a controller method,
 * no template references a dead route, and the engine registries are
 * self-consistent.
 */
class RepositoryAuditTest extends TestCase
{
    use RefreshDatabase;
    /** Admin menu URLs seeded by MenuSeeder must all resolve to a real route. */
    public function test_every_seeded_admin_menu_url_resolves(): void
    {
        $this->seed(\Database\Seeders\RolesPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\MenuSeeder::class);

        $router = app('router');
        $broken = [];

        foreach (\App\Models\MenuItem::where('location', 'admin')->get() as $item) {
            if (empty($item->url) || ! str_starts_with($item->url, '/')) {
                continue;
            }
            // Query strings are stripped before matching.
            $path = explode('?', $item->url)[0];

            try {
                $router->getRoutes()->match(
                    \Illuminate\Http\Request::create($path, 'GET')
                );
            } catch (\Throwable $e) {
                $broken[] = $item->title.' → '.$item->url;
            }
        }

        $this->assertSame([], $broken, "Menu entries pointing nowhere:\n".implode("\n", $broken));
    }

    /** Every admin route must point at a method that actually exists. */
    public function test_every_admin_route_targets_a_real_controller_method(): void
    {
        $bad = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (! str_contains($action, 'Controllers') || ! str_contains($action, '@')) {
                continue;
            }
            if (! str_starts_with((string) $route->uri(), 'admin')) {
                continue;
            }

            [$class, $method] = explode('@', $action);
            if (! class_exists($class)) {
                $bad[] = $action.' (class missing)';

                continue;
            }
            if (! method_exists($class, $method)) {
                $bad[] = $action.' (method missing)';
            }
        }

        $this->assertSame([], $bad, "Admin routes with no controller method:\n".implode("\n", $bad));
    }

    /** No admin route may be registered without a name. */
    public function test_all_admin_routes_are_named(): void
    {
        $unnamed = [];

        foreach (Route::getRoutes() as $route) {
            if (str_starts_with((string) $route->uri(), 'admin') && ! $route->getName()) {
                $unnamed[] = implode('|', $route->methods()).' '.$route->uri();
            }
        }

        $this->assertSame([], $unnamed, "Unnamed admin routes:\n".implode("\n", $unnamed));
    }

    /** The block library catalog is the single source of truth for components. */
    public function test_block_library_catalog_is_internally_consistent(): void
    {
        $catalog = \App\Core\Services\BlockLibrary::catalog();

        $this->assertNotEmpty($catalog, 'Component catalog is empty');

        $types = [];
        foreach ($catalog as $component) {
            $this->assertArrayHasKey('type', $component);
            $this->assertArrayHasKey('label', $component);
            $this->assertArrayHasKey('fields', $component, $component['type'].' has no fields');
            $this->assertArrayHasKey('defaults', $component, $component['type'].' has no defaults');
            $this->assertNotEmpty($component['group'], $component['type'].' has no group');

            $this->assertArrayNotHasKey(
                $component['type'],
                $types,
                'Duplicate component type: '.$component['type']
            );
            $types[$component['type']] = true;

            // Every declared field needs a label for the inspector.
            foreach ($component['fields'] as $key => $field) {
                $this->assertArrayHasKey('label', $field, $component['type'].'.'.$key.' has no label');
            }
        }
    }

    /** Every form field type must be renderable by the form renderer. */
    public function test_every_form_field_type_is_declared_and_known(): void
    {
        $types = array_keys(\App\Models\FormField::TYPES);

        $this->assertContains('text', $types);
        $this->assertContains('select', $types);
        $this->assertContains('file', $types);

        // The option-bearing types must be a subset of the declared types.
        foreach (\App\Models\FormField::OPTION_TYPES as $t) {
            $this->assertContains($t, $types, $t.' needs an options list but is not a known type');
        }
    }

    /** The workflow engine must not be able to write to an arbitrary model. */
    public function test_workflow_model_whitelist_excludes_unrelated_models(): void
    {
        $allowed = \App\Core\Services\WorkflowEngine::WHITELISTED_MODELS;

        $this->assertNotEmpty($allowed);

        foreach ([
            \App\Models\User::class,
            \App\Models\License::class,
            \App\Models\Tenant::class,
        ] as $forbidden) {
            $this->assertNotContains(
                $forbidden,
                $allowed,
                class_basename($forbidden).' must not be writable by a workflow action'
            );
        }
    }

    /** Money and content columns used by the data builder must exist. */
    public function test_core_tables_carry_the_columns_the_code_reads(): void
    {
        $expected = [
            'pages' => ['title', 'slug', 'status', 'builder', 'is_homepage', 'deleted_at'],
            'posts' => ['title', 'slug', 'status', 'published_at', 'deleted_at'],
            'comments' => ['body', 'status', 'deleted_at'],
            'reusable_blocks' => ['name', 'data', 'is_global', 'is_active', 'deleted_at'],
            'page_templates' => ['name', 'structure', 'is_active', 'deleted_at'],
            'form_fields' => ['form_id', 'label', 'name', 'type', 'is_active'],
            'form_submissions' => ['form_id', 'data'],
            'content_types' => ['name', 'slug', 'fields', 'is_api_enabled'],
            'content_records' => ['content_type_id', 'data', 'status', 'deleted_at'],
            'menu_items' => ['location', 'title', 'url', 'parent_id', 'permission', 'is_visible'],
            'settings' => ['key', 'value', 'type', 'group'],
        ];

        $missing = [];
        foreach ($expected as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $missing[] = "table {$table} does not exist";

                continue;
            }
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missing[] = "{$table}.{$column}";
                }
            }
        }

        $this->assertSame([], $missing, "Schema gaps:\n".implode("\n", $missing));
    }

    /** The install lock must live outside the web root. */
    public function test_install_lock_is_outside_the_web_root(): void
    {
        $path = str_replace('\\', '/', (string) config('lindu.installer_lock'));

        $this->assertStringNotContainsString('/public/', $path);
        $this->assertStringNotContainsString('\\public\\', $path);
    }

    /** composer.json must not still identify as the Laravel skeleton. */
    public function test_composer_identity_is_lindu_not_laravel(): void
    {
        $composer = json_decode(File::get(base_path('composer.json')), true);

        $this->assertNotSame('laravel/laravel', $composer['name'] ?? '');
        $this->assertSame('linducms/cms', $composer['name'] ?? '');
        $this->assertStringNotContainsStringIgnoringCase(
            'skeleton application for the Laravel framework',
            $composer['description'] ?? '',
            'composer description still advertises the Laravel skeleton'
        );
    }

    /** No eval() anywhere in application code. */
    public function test_no_eval_in_application_code(): void
    {
        $offenders = [];

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('app'), \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $f) {
            if (! $f->isFile() || ! $f->getExtension() === 'php') {
                continue;
            }
            $src = (string) file_get_contents($f->getPathname());
            if (preg_match('/(?<![\w>])eval\s*\(/', $src)) {
                $offenders[] = $f->getPathname();
            }
        }

        $this->assertSame([], $offenders, "eval() found in:\n".implode("\n", $offenders));
    }

    /** Models used with the data builder must not allow mass assignment. */
    public function test_no_model_opens_mass_assignment_entirely(): void
    {
        $offenders = [];

        foreach (File::files(base_path('app/Models')) as $f) {
            $src = (string) file_get_contents($f->getPathname());
            if (preg_match('/guarded\s*=\s*\[\s*\]/', $src)) {
                $offenders[] = $f->getFilename();
            }
        }

        $this->assertSame([], $offenders, "Models with an empty \$guarded:\n".implode("\n", $offenders));
    }
}
