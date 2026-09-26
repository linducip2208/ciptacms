<?php
namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        MenuItem::query()->delete();

        $root = fn (string $title, string $icon, int $sort, array $meta = []) => MenuItem::create([
            'location' => 'admin', 'title' => $title, 'icon' => $icon,
            'sort_order' => $sort, 'is_visible' => true, 'module' => 'core',
            'parent_id' => null, 'meta' => $meta,
        ]);

        $child = function (MenuItem $parent, string $title, string $icon, string $url, int $sort, ?string $permission = null, ?string $module = null) {
            return MenuItem::create([
                'location' => 'admin', 'parent_id' => $parent->id, 'title' => $title,
                'icon' => $icon, 'url' => $url, 'sort_order' => $sort,
                'permission' => $permission, 'is_visible' => true, 'module' => $module ?? 'core',
            ]);
        };

        // CONTENT
        $content = $root('Content', 'ti ti-file-text', 10);
        $child($content, 'Pages', 'ti ti-file', '/admin/cms/pages', 10, 'pages.view');
        $child($content, 'Posts', 'ti ti-pencil', '/admin/cms/posts', 20, 'posts.view');
        $child($content, 'Categories', 'ti ti-folder', '/admin/r/categories', 30, 'categories.view');
        $child($content, 'Tags', 'ti ti-tag', '/admin/r/tags', 40, 'tags.view');
        $child($content, 'Media Library', 'ti ti-photo', '/admin/media', 50, 'media.view');
        $child($content, 'Comments', 'ti ti-messages', '/admin/cms/comments', 60, 'comments.view');
        $child($content, 'Revisions', 'ti ti-history', '/admin/cms/revisions', 70, 'pages.view');
        $child($content, 'Trash', 'ti ti-trash', '/admin/cms/trash', 80, 'pages.view');

        // APPEARANCE
        $appearance = $root('Appearance', 'ti ti-palette', 20);
        $child($appearance, 'Themes', 'ti ti-paint', '/admin/themes', 10, 'themes.view');
        $child($appearance, 'Theme Customizer', 'ti ti-adjustments', '/admin/themes/customize', 20, 'themes.manage');
        $child($appearance, 'Menus', 'ti ti-menu-2', '/admin/menus', 30, 'menus.view');
        $child($appearance, 'Widgets', 'ti ti-apps', '/admin/widgets', 40, 'widgets.view');
        $child($appearance, 'Header', 'ti ti-header', '/admin/appearance/header', 50, 'themes.manage');
        $child($appearance, 'Footer', 'ti ti-footer', '/admin/appearance/footer', 60, 'themes.manage');
        $child($appearance, 'Homepage', 'ti ti-home-2', '/admin/appearance/homepage', 70, 'themes.manage');
        $child($appearance, 'Templates', 'ti ti-copy', '/admin/cms/templates', 80, 'pages.view');
        $child($appearance, 'Global Blocks', 'ti ti-blocks', '/admin/cms/blocks', 90, 'pages.view');
        $child($appearance, 'Custom CSS / JS', 'ti ti-code', '/admin/appearance/custom-code', 100, 'settings.manage');

        // PAGE BUILDER
        $builder = $root('Page Builder', 'ti ti-layout', 30);
        $child($builder, 'Pages Builder', 'ti ti-layout-grid', '/admin/cms/pages', 10, 'pages.view');
        $child($builder, 'Sections', 'ti ti-divide', '/admin/cms/sections', 20, 'pages.view');
        $child($builder, 'Components', 'ti ti-box', '/admin/cms/components', 30, 'pages.view');
        $child($builder, 'Blocks', 'ti ti-blocks', '/admin/cms/blocks', 40, 'pages.view');
        $child($builder, 'Templates', 'ti ti-copy', '/admin/cms/templates', 50, 'pages.view');
        $child($builder, 'Saved Blocks', 'ti ti-bookmark', '/admin/cms/blocks', 60, 'pages.view');

        // FORMS
        $forms = $root('Forms', 'ti ti-forms', 40);
        $child($forms, 'Forms', 'ti ti-form', '/admin/cms/forms', 10, 'forms.view');
        $child($forms, 'Form Fields', 'ti ti-list', '/admin/cms/form-fields', 20, 'forms.view');
        $child($forms, 'Submissions', 'ti ti-inbox', '/admin/cms/submissions', 30, 'forms.view');
        $child($forms, 'Email Notifications', 'ti ti-mail', '/admin/cms/form-notifications', 40, 'forms.manage');
        $child($forms, 'Spam / Protection', 'ti ti-shield-x', '/admin/cms/form-spam', 50, 'forms.manage');

        // MEDIA
        $media = $root('Media', 'ti ti-photo', 50);
        $child($media, 'Library', 'ti ti-photo', '/admin/media', 10, 'media.view');
        $child($media, 'Folders', 'ti ti-folder', '/admin/media/folders', 20, 'media.view');
        $child($media, 'Images', 'ti ti-photo-plus', '/admin/media?type=image', 30, 'media.view');
        $child($media, 'Documents', 'ti ti-file-text', '/admin/media?type=document', 40, 'media.view');
        $child($media, 'Videos', 'ti ti-video', '/admin/media?type=video', 50, 'media.view');
        $child($media, 'Storage', 'ti ti-device-desktop', '/admin/media/storage', 60, 'media.manage');

        // SEO
        $seo = $root('SEO', 'ti ti-search', 60);
        $child($seo, 'Global SEO', 'ti ti-settings', '/admin/cms/seo', 10, 'seo.view');
        $child($seo, 'Page SEO', 'ti ti-file', '/admin/cms/seo?type=page', 20, 'seo.view');
        $child($seo, 'Post SEO', 'ti ti-pencil', '/admin/cms/seo?type=post', 30, 'seo.view');
        $child($seo, 'Sitemap', 'ti ti-map-2', '/admin/cms/seo/sitemap', 40, 'seo.view');
        $child($seo, 'Robots.txt', 'ti ti-robot', '/admin/cms/seo/robots', 50, 'seo.manage');
        $child($seo, 'Redirects', 'ti ti-arrow-right-left', '/admin/cms/seo/redirects', 60, 'seo.manage');
        $child($seo, 'Schema', 'ti ti-braces', '/admin/cms/seo/schema', 70, 'seo.manage');
        $child($seo, 'Open Graph', 'ti ti-share', '/admin/cms/seo', 80, 'seo.view');

        // USERS
        $users = $root('Users', 'ti ti-users', 70);
        $child($users, 'Users', 'ti ti-user', '/admin/users', 10, 'users.view');
        $child($users, 'Roles', 'ti ti-shield', '/admin/roles', 20, 'roles.view');
        $child($users, 'Permissions', 'ti ti-key', '/admin/permissions', 30, 'roles.view');
        $child($users, 'Groups', 'ti ti-users-group', '/admin/permissions/groups', 40, 'roles.view');
        $child($users, 'Login History', 'ti ti-login', '/admin/security/login-history', 50, 'users.view');
        $child($users, 'Sessions', 'ti ti-devices', '/admin/security/sessions', 60, 'users.view');
        $child($users, 'Security', 'ti ti-shield-lock', '/admin/security/2fa', 70, 'users.manage');

        // MODULES
        $modules = $root('Modules', 'ti ti-box', 80);
        $child($modules, 'Installed', 'ti ti-package', '/admin/modules?filter=installed', 10, 'modules.view');
        $child($modules, 'Available', 'ti ti-apps', '/admin/modules?filter=available', 20, 'modules.view');
        $child($modules, 'Active', 'ti ti-circle-check', '/admin/modules?filter=active', 30, 'modules.view');
        $child($modules, 'Inactive', 'ti ti-circle-x', '/admin/modules?filter=inactive', 40, 'modules.view');
        $child($modules, 'Updates', 'ti ti-refresh', '/admin/updates/modules', 50, 'modules.view');

        // PLUGINS
        $plugins = $root('Plugins', 'ti ti-plug', 90);
        $child($plugins, 'Installed', 'ti ti-package', '/admin/plugins?filter=installed', 10, 'plugins.view');
        $child($plugins, 'Active', 'ti ti-circle-check', '/admin/plugins?filter=active', 20, 'plugins.view');
        $child($plugins, 'Inactive', 'ti ti-circle-x', '/admin/plugins?filter=inactive', 30, 'plugins.view');
        $child($plugins, 'Updates', 'ti ti-refresh', '/admin/updates/plugins', 40, 'plugins.view');
        $child($plugins, 'Settings', 'ti ti-settings', '/admin/plugins/settings', 50, 'plugins.manage');

        // DATA
        $data = $root('Data', 'ti ti-database', 100);
        $child($data, 'Content Types', 'ti ti-database', '/admin/cms/content-types', 10, 'content-types.view');
        $child($data, 'Fields', 'ti ti-list-details', '/admin/cms/content-types/fields', 20, 'content-types.view');
        $child($data, 'Relations', 'ti ti-topology-star', '/admin/cms/content-types/relations', 30, 'content-types.view');
        $child($data, 'Records', 'ti ti-table', '/admin/cms/records', 40, 'content-records.view');
        $child($data, 'Import', 'ti ti-upload', '/admin/cms/import', 50, 'content-records.import');
        $child($data, 'Export', 'ti ti-download', '/admin/cms/export', 60, 'content-records.export');

        // WORKFLOW
        $workflow = $root('Workflow', 'ti ti-bolt', 110);
        $child($workflow, 'Workflows', 'ti ti-bolt', '/admin/cms/workflows', 10, 'workflows.view');
        $child($workflow, 'Triggers', 'ti ti-play', '/admin/cms/workflows/triggers', 20, 'workflows.view');
        $child($workflow, 'Conditions', 'ti ti-filter', '/admin/cms/workflows/conditions', 30, 'workflows.view');
        $child($workflow, 'Actions', 'ti ti-arrow-right-circle', '/admin/cms/workflows/actions', 40, 'workflows.view');
        $child($workflow, 'Tasks', 'ti ti-checkbox', '/admin/r/tasks', 50, 'tasks.view');
        $child($workflow, 'Execution Logs', 'ti ti-terminal-2', '/admin/cms/workflows/runs', 60, 'workflows.view');

        // NOTIFICATIONS
        $notifications = $root('Notifications', 'ti ti-bell', 120);
        $child($notifications, 'Notifications', 'ti ti-bell', '/admin/notifications', 10, 'notifications.view');
        $child($notifications, 'Email', 'ti ti-mail', '/admin/notifications?channel=mail', 20, 'notifications.view');
        $child($notifications, 'Webhook', 'ti ti-webhook', '/admin/cms/webhooks', 30, 'webhooks.view');
        $child($notifications, 'Push', 'ti ti-device-mobile', '/admin/notifications?channel=push', 40, 'notifications.view');
        $child($notifications, 'Templates', 'ti ti-template', '/admin/notifications/templates', 50, 'notifications.view');

        // COMMENTS
        $comments = $root('Comments', 'ti ti-message-circle', 130);
        $child($comments, 'All Comments', 'ti ti-messages', '/admin/cms/comments', 10, 'comments.view');
        $child($comments, 'Pending', 'ti ti-clock', '/admin/cms/comments?status=pending', 20, 'comments.view');
        $child($comments, 'Approved', 'ti ti-circle-check', '/admin/cms/comments?status=approved', 30, 'comments.view');
        $child($comments, 'Spam', 'ti ti-alert-triangle', '/admin/cms/comments?status=spam', 40, 'comments.view');
        $child($comments, 'Trash', 'ti ti-trash', '/admin/cms/comments?status=trash', 50, 'comments.view');
        $child($comments, 'Word Filter', 'ti ti-letter-case', '/admin/cms/comments/word-filter', 60, 'comments.manage');
        $child($comments, 'Reports', 'ti ti-flag', '/admin/cms/comments/reports', 70, 'comments.view');

        // SETTINGS
        $settings = $root('Settings', 'ti ti-settings', 140);
        $child($settings, 'General', 'ti ti-settings', '/admin/settings/general', 10, 'settings.view');
        $child($settings, 'Branding', 'ti ti-brand-facebook', '/admin/settings/branding', 20, 'settings.view');
        $child($settings, 'Site Identity', 'ti ti-world', '/admin/settings/identity', 30, 'settings.view');
        $child($settings, 'Localization', 'ti ti-language', '/admin/settings/localization', 40, 'settings.view');
        $child($settings, 'Email', 'ti ti-mail', '/admin/settings/email', 50, 'settings.view');
        $child($settings, 'Storage', 'ti ti-device-desktop', '/admin/settings/storage', 60, 'settings.view');
        $child($settings, 'Media', 'ti ti-photo', '/admin/settings/media', 70, 'settings.view');
        $child($settings, 'SEO', 'ti ti-search', '/admin/settings/seo', 80, 'settings.view');
        $child($settings, 'Security', 'ti ti-shield-lock', '/admin/settings/security', 90, 'settings.view');
        $child($settings, 'API', 'ti ti-api', '/admin/settings/api', 100, 'settings.view');
        $child($settings, 'Webhooks', 'ti ti-webhook', '/admin/settings/webhooks', 110, 'settings.view');
        $child($settings, 'Cache', 'ti ti-database-import', '/admin/settings/cache', 120, 'settings.view');
        $child($settings, 'Queue', 'ti ti-stack-2', '/admin/settings/queue', 130, 'settings.view');
        $child($settings, 'Notifications', 'ti ti-bell', '/admin/settings/notifications', 140, 'settings.view');
        $child($settings, 'Social', 'ti ti-brand-twitter', '/admin/settings/social', 150, 'settings.view');
        $child($settings, 'Maintenance', 'ti ti-tool', '/admin/settings/maintenance', 160, 'settings.manage');
        $child($settings, 'Advanced', 'ti ti-adjustments-horizontal', '/admin/settings/advanced', 170, 'settings.manage');

        // SYSTEM
        $system = $root('System', 'ti ti-server', 150);
        $child($system, 'System Health', 'ti ti-heartbeat', '/admin/health', 10, 'system.view');
        $child($system, 'Logs', 'ti ti-file-text', '/admin/logs', 20, 'system.view');
        $child($system, 'Audit Logs', 'ti ti-list-check', '/admin/audits', 30, 'system.view');
        $child($system, 'Activity Logs', 'ti ti-activity', '/admin/system/activity', 40, 'system.view');
        $child($system, 'Jobs', 'ti ti-clock', '/admin/queue', 50, 'system.view');
        $child($system, 'Scheduled Tasks', 'ti ti-calendar-time', '/admin/system/schedule', 60, 'system.view');
        $child($system, 'Cache', 'ti ti-database-import', '/admin/system/cache', 70, 'system.manage');
        $child($system, 'Storage', 'ti ti-device-desktop', '/admin/system/storage', 80, 'system.view');
        $child($system, 'Database', 'ti ti-database', '/admin/system/database', 90, 'system.view');
        $child($system, 'Backup', 'ti ti-database-export', '/admin/backups', 100, 'system.manage');
        $child($system, 'Restore', 'ti ti-database-import', '/admin/backups/restore', 110, 'system.manage');
        $child($system, 'Maintenance', 'ti ti-tool', '/admin/system/maintenance', 120, 'system.manage');

        // DEVELOPER
        $developer = $root('Developer', 'ti ti-code', 160);
        $child($developer, 'API', 'ti ti-api', '/admin/api-docs', 10, 'system.view');
        $child($developer, 'API Keys', 'ti ti-key', '/admin/settings/api', 20, 'settings.view');
        $child($developer, 'Webhooks', 'ti ti-webhook', '/admin/cms/webhooks', 30, 'webhooks.view');
        $child($developer, 'Events', 'ti ti-bolt', '/admin/developer/events', 40, 'system.view');
        $child($developer, 'Hooks', 'ti ti-plug', '/admin/developer/hooks', 50, 'system.view');
        $child($developer, 'Cache', 'ti ti-database-import', '/admin/system/cache', 60, 'system.manage');
        $child($developer, 'Documentation', 'ti ti-book', '/docs', 70, null);
    }
}
