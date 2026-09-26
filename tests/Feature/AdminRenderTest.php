<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Every admin GET route must render for a logged-in user. A route that 500s
 * is a real defect, not a cosmetic one — an operator cannot work around it.
 */
class AdminRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);

        $user = User::create([
            'name' => 'A',
            'email' => 'a@a.local',
            'password' => Hash::make('password123'),
            'status' => 'active',
            'is_active' => true,
        ]);

        $user->roles()->attach(\App\Models\Role::where('slug', 'admin')->first());

        return $user;
    }

    public static function adminUrls(): array
    {
        return [
            '/admin', '/admin/users', '/admin/roles', '/admin/permissions',
            '/admin/permissions/groups', '/admin/menus', '/admin/modules', '/admin/plugins',
            '/admin/plugins/settings', '/admin/themes', '/admin/themes/customize',
            '/admin/appearance/header', '/admin/appearance/footer', '/admin/appearance/homepage',
            '/admin/appearance/custom-code', '/admin/widgets', '/admin/media',
            '/admin/media/folders', '/admin/media/storage',
            '/admin/settings', '/admin/settings/raw', '/admin/settings/general',
            '/admin/settings/branding', '/admin/settings/identity', '/admin/settings/localization',
            '/admin/settings/email', '/admin/settings/storage', '/admin/settings/media',
            '/admin/settings/seo', '/admin/settings/security', '/admin/settings/api',
            '/admin/settings/webhooks', '/admin/settings/cache', '/admin/settings/queue',
            '/admin/settings/notifications', '/admin/settings/social', '/admin/settings/maintenance',
            '/admin/settings/advanced',
            '/admin/cms/pages', '/admin/cms/posts', '/admin/cms/comments',
            '/admin/cms/comments/word-filter', '/admin/cms/comments/reports',
            '/admin/cms/revisions', '/admin/cms/trash', '/admin/cms/sections',
            '/admin/cms/components', '/admin/cms/blocks', '/admin/cms/templates',
            '/admin/cms/forms', '/admin/cms/form-fields', '/admin/cms/submissions',
            '/admin/cms/form-notifications', '/admin/cms/form-spam',
            '/admin/cms/content-types', '/admin/cms/content-types/fields',
            '/admin/cms/content-types/relations', '/admin/cms/records',
            '/admin/cms/import', '/admin/cms/export',
            '/admin/cms/workflows', '/admin/cms/workflows/triggers',
            '/admin/cms/workflows/conditions', '/admin/cms/workflows/actions',
            '/admin/cms/workflows/runs',
            '/admin/cms/webhooks', '/admin/cms/webhooks/logs', '/admin/cms/webhooks/incoming',
            '/admin/cms/seo', '/admin/cms/seo/sitemap', '/admin/cms/seo/robots',
            '/admin/cms/seo/redirects', '/admin/cms/seo/schema',
            '/admin/notifications', '/admin/notifications/templates', '/admin/notifications/deliveries',
            '/admin/security/2fa', '/admin/security/sessions', '/admin/security/login-history',
            '/admin/saas/tenants', '/admin/saas/plans', '/admin/saas/features',
            '/admin/saas/subscriptions', '/admin/saas/usage', '/admin/saas/domains',
            '/admin/saas/billing', '/admin/gateways',
            '/admin/white-label', '/admin/white-label/branding', '/admin/white-label/login',
            '/admin/white-label/email', '/admin/white-label/code', '/admin/white-label/errors',
            '/admin/white-label/advanced', '/admin/white-label/domains/create',
            '/admin/license',
            '/admin/updates', '/admin/updates/modules', '/admin/updates/plugins',
            '/admin/updates/themes', '/admin/updates/history',
            '/admin/developer/events', '/admin/developer/hooks', '/admin/api-docs',
            '/admin/health', '/admin/info', '/admin/logs', '/admin/audits',
            '/admin/system/activity', '/admin/system/schedule', '/admin/system/cache',
            '/admin/system/storage', '/admin/system/database', '/admin/system/maintenance',
            '/admin/backups', '/admin/backups/restore', '/admin/queue',
            '/admin/search?q=x',
            '/admin/r/products', '/admin/r/orders', '/admin/r/leads',
            '/admin/company', '/admin/company/about', '/admin/company/contact',
            '/admin/company/messages', '/admin/company/applications', '/admin/company/albums',
            '/admin/company/data/services', '/admin/company/data/products',
            '/admin/company/data/portfolio', '/admin/company/data/team',
            '/admin/company/data/testimonials', '/admin/company/data/clients',
            '/admin/company/data/faqs', '/admin/company/data/careers',
            '/admin/company/data/services/trash',
        ];
    }

    public function test_all_admin_pages_render(): void
    {
        $user = $this->admin();

        foreach (self::adminUrls() as $url) {
            $this->actingAs($user)->get($url)->assertOk("GET {$url}");
        }
    }
}
