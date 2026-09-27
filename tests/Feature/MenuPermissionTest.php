<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The admin sidebar is filtered per item by a permission slug.
 * MenuService::visible() hides an item whose permission cannot be resolved,
 * so a guard with no matching permissions row silently removes that whole
 * section — for every role except admin, which bypasses via hasRole().
 *
 * These tests pin the relationship between the two.
 */
class MenuPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function editor(): User
    {
        $this->seed(RolesPermissionsSeeder::class);
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\MenuSeeder::class);

        $user = User::create([
            'name' => 'Ed', 'email' => 'ed@e.local',
            'password' => Hash::make('password123'),
            'status' => 'active', 'is_active' => true,
        ]);

        $role = Role::create(['name' => 'Editor', 'slug' => 'editor']);
        $role->permissions()->attach(
            Permission::whereIn('slug', ['pages.view', 'posts.view', 'media.view'])->pluck('id')
        );
        $user->roles()->attach($role);

        return $user;
    }

    public function test_every_menu_guard_has_a_permission_row(): void
    {
        $this->editor();

        $existing = Permission::pluck('slug')->flip();

        $missing = MenuItem::pluck('permission')
            ->filter()
            ->unique()
            ->reject(fn ($slug) => isset($existing[$slug]))
            ->values()
            ->all();

        $this->assertSame(
            [],
            $missing,
            "These menu items are guarded by a permission that does not exist, so they are "
            .'invisible to every non-admin role: '.implode(', ', $missing)
        );
    }

    public function test_an_editor_sees_the_sections_they_have_permission_for(): void
    {
        $user = $this->editor();

        $visible = collect(
            app(\App\Core\Services\MenuService::class)->tree('admin', $user)
        );

        $titles = $this->flattenTitles($visible);

        $this->assertContains('Pages', $titles, 'an editor with pages.view cannot see the Pages item');
        $this->assertContains('Posts', $titles, 'an editor with posts.view cannot see the Posts item');

        // Items they were not granted stay out. The "Users" group heading is
        // itself unguarded, so assert on the leaf the group contains instead
        // of the group title.
        $this->assertNotContains(
            'Users',
            $this->flattenTitles(app(\App\Core\Services\MenuService::class)->tree('admin', $user)['Users']['children'] ?? []),
            'an editor should not see the Users list without users.view'
        );
        $this->assertNotContains('Users', $titles, 'an editor should not see Users without users.view');
    }

    public function test_the_admin_role_sees_everything_regardless(): void
    {
        $this->editor();

        $admin = User::create([
            'name' => 'Ad', 'email' => 'ad@a.local',
            'password' => Hash::make('password123'),
            'status' => 'active', 'is_active' => true,
        ]);
        $admin->roles()->attach(Role::where('slug', 'admin')->first());

        $titles = $this->flattenTitles(
            app(\App\Core\Services\MenuService::class)->tree('admin', $admin)
        );

        $this->assertContains('Users', $titles);
        $this->assertContains('Settings', $titles);
        $this->assertContains('System', $titles);
    }

    /**
     * MenuService::tree() returns an array at the top but nest() yields
     * Collections, so normalise before recursing.
     */
    protected function flattenTitles($items): array
    {
        $out = [];
        foreach ($items as $item) {
            $out[] = $item['title'];
            foreach ($this->flattenTitles($item['children'] ?? []) as $child) {
                $out[] = $child;
            }
        }

        return $out;
    }
}
