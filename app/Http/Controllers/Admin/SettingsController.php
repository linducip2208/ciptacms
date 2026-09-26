<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\SettingService;
use Illuminate\Http\Request;

/**
 * Database-driven settings. Every tab below writes real keys through
 * SettingService, which encrypts `secret` typed values and busts cache.
 */
class SettingsController extends AdminController
{
    /**
     * Field definitions per tab. A tab is a real group of settings that the
     * operator can edit — no tab exists without a form and a save handler.
     */
    public const TABS = [
        'general' => [
            'label' => 'General',
            'group' => 'general',
            'fields' => [
                ['key' => 'general.site_name', 'label' => 'Site name', 'type' => 'text', 'default' => 'Lindu CMS'],
                ['key' => 'general.tagline', 'label' => 'Tagline', 'type' => 'text', 'default' => 'Building digital products that grow with you.'],
                ['key' => 'general.footer', 'label' => 'Footer text', 'type' => 'text', 'default' => 'All rights reserved.'],
                ['key' => 'general.services_intro', 'label' => 'Services intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.products_intro', 'label' => 'Products intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.portfolio_intro', 'label' => 'Portfolio intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.team_intro', 'label' => 'Team intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.testimonials_intro', 'label' => 'Testimonials intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.clients_intro', 'label' => 'Clients intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.faq_intro', 'label' => 'FAQ intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.gallery_intro', 'label' => 'Gallery intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.careers_intro', 'label' => 'Careers intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.contact_intro', 'label' => 'Contact intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.blog_intro', 'label' => 'Blog intro', 'type' => 'textarea', 'default' => ''],
                ['key' => 'general.founded_year', 'label' => 'Founded year', 'type' => 'number', 'default' => ''],
            ],
        ],
        'branding' => [
            'label' => 'Branding',
            'group' => 'branding',
            'fields' => [
                ['key' => 'branding.logo', 'label' => 'Logo URL', 'type' => 'image', 'default' => '', 'help' => 'Shown in the site header. Leave empty to use the text mark.'],
                ['key' => 'branding.favicon', 'label' => 'Favicon URL', 'type' => 'image', 'default' => '/favicon.ico'],
                ['key' => 'branding.login_logo', 'label' => 'Login logo', 'type' => 'image', 'default' => '', 'help' => 'Used on the admin and front-end login screens.'],
                ['key' => 'branding.login_background', 'label' => 'Login background URL', 'type' => 'image', 'default' => ''],
                ['key' => 'branding.primary_color', 'label' => 'Primary colour', 'type' => 'color', 'default' => '#1d4ed8'],
                ['key' => 'branding.secondary_color', 'label' => 'Secondary colour', 'type' => 'color', 'default' => '#0f172a'],
                ['key' => 'branding.radius', 'label' => 'Corner radius', 'type' => 'text', 'default' => '12px'],
                ['key' => 'branding.og_image', 'label' => 'Default share image', 'type' => 'image', 'default' => '', 'help' => 'Fallback OpenGraph image for pages without their own.'],
                ['key' => 'branding.admin_title', 'label' => 'Admin panel title', 'type' => 'text', 'default' => ''],
                ['key' => 'branding.email_from_name', 'label' => 'Email sender name', 'type' => 'text', 'default' => ''],
                ['key' => 'branding.email_from_address', 'label' => 'Email sender address', 'type' => 'text', 'default' => ''],
                ['key' => 'branding.footer_branding', 'label' => 'Footer branding text', 'type' => 'text', 'default' => '', 'help' => 'Leave empty to hide any vendor credit.'],
            ],
        ],
        'identity' => [
            'label' => 'Site Identity',
            'group' => 'identity',
            'fields' => [
                ['key' => 'identity.name', 'label' => 'Legal / registered name', 'type' => 'text', 'default' => ''],
                ['key' => 'identity.short_name', 'label' => 'Short name', 'type' => 'text', 'default' => ''],
                ['key' => 'identity.registration_number', 'label' => 'Company registration no.', 'type' => 'text', 'default' => ''],
                ['key' => 'identity.tax_id', 'label' => 'Tax / NPWP number', 'type' => 'text', 'default' => ''],
                ['key' => 'identity.legal_address', 'label' => 'Registered address', 'type' => 'textarea', 'default' => ''],
                ['key' => 'identity.support_email', 'label' => 'Support email', 'type' => 'text', 'default' => ''],
                ['key' => 'identity.support_phone', 'label' => 'Support phone', 'type' => 'text', 'default' => ''],
            ],
        ],
        'localization' => [
            'label' => 'Localization',
            'group' => 'localization',
            'fields' => [
                ['key' => 'localization.locale', 'label' => 'Default locale', 'type' => 'text', 'default' => 'en', 'help' => 'BCP-47 code, e.g. en, id, ar.'],
                ['key' => 'localization.fallback_locale', 'label' => 'Fallback locale', 'type' => 'text', 'default' => 'en'],
                ['key' => 'localization.timezone', 'label' => 'Timezone', 'type' => 'select', 'default' => 'Asia/Jakarta', 'options' => [
                    'Asia/Jakarta' => 'Asia/Jakarta (WIB)', 'Asia/Makassar' => 'Asia/Makassar (WITA)',
                    'Asia/Jayapura' => 'Asia/Jayapura (WIT)', 'Asia/Singapore' => 'Asia/Singapore',
                    'UTC' => 'UTC',
                ]],
                ['key' => 'localization.currency', 'label' => 'Currency', 'type' => 'select', 'default' => 'IDR', 'options' => [
                    'IDR' => 'IDR — Indonesian Rupiah', 'USD' => 'USD — US Dollar', 'EUR' => 'EUR — Euro',
                    'SGD' => 'SGD — Singapore Dollar', 'MYR' => 'MYR — Malaysian Ringgit', 'GBP' => 'GBP — Pound Sterling',
                ]],
                ['key' => 'localization.currency_symbol', 'label' => 'Currency symbol', 'type' => 'text', 'default' => 'Rp'],
                ['key' => 'localization.currency_position', 'label' => 'Symbol position', 'type' => 'select', 'default' => 'before', 'options' => ['before' => 'Before — Rp 1.000', 'after' => 'After — 1.000 Rp']],
                ['key' => 'localization.date_format', 'label' => 'Date format', 'type' => 'text', 'default' => 'd M Y'],
                ['key' => 'localization.number_format', 'label' => 'Number format', 'type' => 'text', 'default' => '1.000,00', 'help' => 'Thousands and decimal separators.'],
            ],
        ],
        'email' => [
            'label' => 'Email',
            'group' => 'email',
            'fields' => [
                ['key' => 'email.driver', 'label' => 'Mail driver', 'type' => 'select', 'default' => 'log', 'options' => [
                    'log' => 'Log (writes to storage/logs)', 'smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'logmail' => 'Log + Mail',
                ]],
                ['key' => 'email.host', 'label' => 'SMTP host', 'type' => 'text', 'default' => ''],
                ['key' => 'email.port', 'label' => 'SMTP port', 'type' => 'number', 'default' => '587'],
                ['key' => 'email.username', 'label' => 'SMTP username', 'type' => 'text', 'default' => ''],
                ['key' => 'email.password', 'label' => 'SMTP password', 'type' => 'secret', 'default' => ''],
                ['key' => 'email.encryption', 'label' => 'Encryption', 'type' => 'select', 'default' => 'tls', 'options' => [
                    '' => 'None', 'tls' => 'TLS', 'ssl' => 'SSL',
                ]],
                ['key' => 'email.from_address', 'label' => 'From address', 'type' => 'text', 'default' => ''],
                ['key' => 'email.from_name', 'label' => 'From name', 'type' => 'text', 'default' => ''],
            ],
        ],
        'storage' => [
            'label' => 'Storage',
            'group' => 'storage',
            'fields' => [
                ['key' => 'storage.media_disk', 'label' => 'Media disk', 'type' => 'select', 'default' => 'public', 'options' => [
                    'public' => 'public (local, public URL)', 'local' => 'local (private)',
                    's3' => 's3 (S3-compatible)', 'spaces' => 'spaces (DigitalOcean)',
                ]],
                ['key' => 'storage.cdn_url', 'label' => 'CDN base URL', 'type' => 'text', 'default' => '', 'help' => 'Optional. Files are served from here when set.'],
                ['key' => 'storage.backup_disk', 'label' => 'Backup disk', 'type' => 'select', 'default' => 'local', 'options' => [
                    'local' => 'local', 'public' => 'public', 's3' => 's3',
                ]],
                ['key' => 'storage.keep_backups', 'label' => 'Backups to keep', 'type' => 'number', 'default' => '7'],
                ['key' => 'storage.scheduled_backups', 'label' => 'Scheduled backups', 'type' => 'boolean', 'default' => false, 'help' => 'Runs from the scheduler; see DEPLOYMENT.md.'],
            ],
        ],
        'media' => [
            'label' => 'Media',
            'group' => 'media',
            'fields' => [
                ['key' => 'media.max_upload_mb', 'label' => 'Max upload size (MB)', 'type' => 'number', 'default' => '10'],
                ['key' => 'media.auto_optimize', 'label' => 'Auto-generate WebP/AVIF', 'type' => 'boolean', 'default' => true],
                ['key' => 'media.generate_thumbnails', 'label' => 'Generate thumbnails', 'type' => 'boolean', 'default' => true],
                ['key' => 'media.watermark', 'label' => 'Watermark image URL', 'type' => 'image', 'default' => '', 'help' => 'Optional. Leave empty to disable.'],
            ],
        ],
        'seo' => [
            'label' => 'SEO',
            'group' => 'seo',
            'fields' => [
                ['key' => 'seo.site_name', 'label' => 'SEO site name', 'type' => 'text', 'default' => ''],
                ['key' => 'seo.separator', 'label' => 'Title separator', 'type' => 'text', 'default' => ' | '],
                ['key' => 'seo.meta_description', 'label' => 'Default meta description', 'type' => 'textarea', 'default' => ''],
                ['key' => 'seo.meta_keywords', 'label' => 'Default keywords', 'type' => 'text', 'default' => ''],
                ['key' => 'seo.robots', 'label' => 'Default robots directive', 'type' => 'select', 'default' => 'index,follow', 'options' => [
                    'index,follow' => 'index, follow', 'noindex,follow' => 'noindex, follow',
                    'index,nofollow' => 'index, nofollow', 'noindex,nofollow' => 'noindex, nofollow',
                ]],
                ['key' => 'seo.twitter_card', 'label' => 'Twitter card type', 'type' => 'select', 'default' => 'summary_large_image', 'options' => [
                    'summary_large_image' => 'summary_large_image', 'summary' => 'summary',
                ]],
                ['key' => 'seo.robots_disallow', 'label' => 'robots.txt Disallow paths', 'type' => 'textarea', 'default' => "/admin\n/install\n/login", 'help' => 'One path per line.'],
                ['key' => 'seo.custom_head', 'label' => 'Custom <head> snippet', 'type' => 'textarea', 'default' => '', 'help' => 'Injected into <head> on every public page. Verification tags go here.'],
                ['key' => 'seo.schema_organization', 'label' => 'Organization schema (JSON)', 'type' => 'textarea', 'default' => '', 'help' => 'JSON-LD merged into the default Organization markup.'],
            ],
        ],
        'security' => [
            'label' => 'Security',
            'group' => 'security',
            'fields' => [
                ['key' => 'security.force_2fa', 'label' => 'Require 2FA for all admins', 'type' => 'boolean', 'default' => false],
                ['key' => 'security.max_login_attempts', 'label' => 'Max login attempts', 'type' => 'number', 'default' => '5'],
                ['key' => 'security.lockout_minutes', 'label' => 'Lockout duration (minutes)', 'type' => 'number', 'default' => '15'],
                ['key' => 'security.session_lifetime', 'label' => 'Session lifetime (minutes)', 'type' => 'number', 'default' => '120'],
                ['key' => 'security.password_min_length', 'label' => 'Minimum password length', 'type' => 'number', 'default' => '8'],
                ['key' => 'security.password_expiry_days', 'label' => 'Password expiry (days, 0 = never)', 'type' => 'number', 'default' => '0'],
                ['key' => 'security.ip_allowlist', 'label' => 'Admin IP allowlist', 'type' => 'textarea', 'default' => '', 'help' => 'One IP or CIDR per line. Empty allows all.'],
                ['key' => 'security.maintenance_mode', 'label' => 'Maintenance mode', 'type' => 'boolean', 'default' => false],
                ['key' => 'security.maintenance_message', 'label' => 'Maintenance message', 'type' => 'textarea', 'default' => 'We are performing scheduled maintenance. Please check back shortly.'],
            ],
        ],
        'api' => [
            'label' => 'API',
            'group' => 'api',
            'fields' => [
                ['key' => 'api.enabled', 'label' => 'Enable REST API', 'type' => 'boolean', 'default' => true],
                ['key' => 'api.rate_limit', 'label' => 'Rate limit (requests/minute)', 'type' => 'number', 'default' => '60'],
                ['key' => 'api.default_version', 'label' => 'Default version', 'type' => 'select', 'default' => 'v1', 'options' => ['v1' => 'v1', 'v2' => 'v2']],
                ['key' => 'api.allow_registration', 'label' => 'Allow self-registration via API', 'type' => 'boolean', 'default' => false],
                ['key' => 'api.cors_origins', 'label' => 'Allowed CORS origins', 'type' => 'textarea', 'default' => '', 'help' => 'One origin per line. Empty uses config/cors.php.'],
            ],
        ],
        'webhooks' => [
            'label' => 'Webhooks',
            'group' => 'webhooks',
            'fields' => [
                ['key' => 'webhooks.timeout', 'label' => 'Request timeout (seconds)', 'type' => 'number', 'default' => '10'],
                ['key' => 'webhooks.max_attempts', 'label' => 'Max retry attempts', 'type' => 'number', 'default' => '3'],
                ['key' => 'webhooks.retry_backoff', 'label' => 'Retry backoff (seconds)', 'type' => 'number', 'default' => '60'],
                ['key' => 'webhooks.signature_header', 'label' => 'Signature header', 'type' => 'text', 'default' => 'X-Lindu-Signature'],
            ],
        ],
        'cache' => [
            'label' => 'Cache',
            'group' => 'cache',
            'fields' => [
                ['key' => 'cache.enabled', 'label' => 'Enable application cache', 'type' => 'boolean', 'default' => true],
                ['key' => 'cache.menu_ttl', 'label' => 'Menu cache TTL (seconds)', 'type' => 'number', 'default' => '120'],
                ['key' => 'cache.settings_ttl', 'label' => 'Settings cache TTL (seconds)', 'type' => 'number', 'default' => '300'],
                ['key' => 'cache.seo_ttl', 'label' => 'SEO cache TTL (seconds)', 'type' => 'number', 'default' => '600'],
            ],
        ],
        'queue' => [
            'label' => 'Queue',
            'group' => 'queue',
            'fields' => [
                ['key' => 'queue.default', 'label' => 'Default queue connection', 'type' => 'select', 'default' => 'database', 'options' => [
                    'sync' => 'sync (run immediately)', 'database' => 'database', 'redis' => 'redis',
                ]],
                ['key' => 'queue.media', 'label' => 'Media processing queue', 'type' => 'text', 'default' => 'media'],
                ['key' => 'queue.webhooks', 'label' => 'Webhook queue', 'type' => 'text', 'default' => 'webhooks'],
                ['key' => 'queue.notifications', 'label' => 'Notification queue', 'type' => 'text', 'default' => 'notifications'],
                ['key' => 'queue.retries', 'label' => 'Job retries', 'type' => 'number', 'default' => '3'],
            ],
        ],
        'notifications' => [
            'label' => 'Notifications',
            'group' => 'notifications',
            'fields' => [
                ['key' => 'notifications.admin_email', 'label' => 'Send admin notifications to', 'type' => 'text', 'default' => ''],
                ['key' => 'notifications.new_contact', 'label' => 'Notify on new contact message', 'type' => 'boolean', 'default' => true],
                ['key' => 'notifications.new_application', 'label' => 'Notify on new job application', 'type' => 'boolean', 'default' => true],
                ['key' => 'notifications.new_submission', 'label' => 'Notify on new form submission', 'type' => 'boolean', 'default' => true],
                ['key' => 'notifications.new_comment', 'label' => 'Notify on new comment', 'type' => 'boolean', 'default' => true],
                ['key' => 'notifications.new_user', 'label' => 'Notify on new user registration', 'type' => 'boolean', 'default' => true],
            ],
        ],
        'social' => [
            'label' => 'Social',
            'group' => 'social',
            'fields' => [
                ['key' => 'social.facebook_client_id', 'label' => 'Facebook app ID', 'type' => 'text', 'default' => ''],
                ['key' => 'social.facebook_client_secret', 'label' => 'Facebook app secret', 'type' => 'secret', 'default' => ''],
                ['key' => 'social.google_client_id', 'label' => 'Google client ID', 'type' => 'text', 'default' => ''],
                ['key' => 'social.google_client_secret', 'label' => 'Google client secret', 'type' => 'secret', 'default' => ''],
                ['key' => 'social.github_client_id', 'label' => 'GitHub client ID', 'type' => 'text', 'default' => ''],
                ['key' => 'social.github_client_secret', 'label' => 'GitHub client secret', 'type' => 'secret', 'default' => ''],
                ['key' => 'social.twitter_client_id', 'label' => 'X / Twitter client ID', 'type' => 'text', 'default' => ''],
                ['key' => 'social.twitter_client_secret', 'label' => 'X / Twitter client secret', 'type' => 'secret', 'default' => ''],
            ],
        ],
        'maintenance' => [
            'label' => 'Maintenance',
            'group' => 'maintenance',
            'fields' => [
                ['key' => 'maintenance.enabled', 'label' => 'Maintenance mode', 'type' => 'boolean', 'default' => false],
                ['key' => 'maintenance.allowed_ips', 'label' => 'Bypass IPs', 'type' => 'textarea', 'default' => '', 'help' => 'One IP per line. These can still reach the site during maintenance.'],
                ['key' => 'maintenance.message', 'label' => 'Message', 'type' => 'textarea', 'default' => 'We will be back shortly.'],
                ['key' => 'maintenance.retry_after', 'label' => 'Retry-After (seconds)', 'type' => 'number', 'default' => '3600'],
            ],
        ],
        'advanced' => [
            'label' => 'Advanced',
            'group' => 'advanced',
            'fields' => [
                ['key' => 'advanced.debug_toolbar', 'label' => 'Show debug info to super admins', 'type' => 'boolean', 'default' => false],
                ['key' => 'advanced.raw_sql', 'label' => 'Allow raw SQL in the data builder', 'type' => 'boolean', 'default' => false, 'help' => 'Off by default. Only enable if you fully trust every user with admin access.'],
                ['key' => 'advanced.asset_version', 'label' => 'Asset version', 'type' => 'text', 'default' => '1', 'help' => 'Bump to bust browser caches.'],
                ['key' => 'advanced.support_url', 'label' => 'Support URL', 'type' => 'text', 'default' => ''],
                ['key' => 'advanced.notes', 'label' => 'Internal notes', 'type' => 'textarea', 'default' => ''],
            ],
        ],
    ];

    public function index()
    {
        return $this->show('general');
    }

    public function tab(Request $r, string $tab)
    {
        if (! isset(self::TABS[$tab])) {
            abort(404, 'Unknown settings tab: '.$tab);
        }

        return $this->show($tab);
    }

    protected function show(string $tab)
    {
        $def = self::TABS[$tab];

        return view('admin.settings.tab', [
            'tab' => $tab,
            'def' => $def,
            'tabs' => self::TABS,
            'values' => app(SettingService::class)->all(),
        ]);
    }

    public function update(Request $r, SettingService $svc)
    {
        $tab = $r->input('tab', 'general');
        if (! isset(self::TABS[$tab])) {
            abort(404);
        }

        $def = self::TABS[$tab];
        $posted = (array) $r->input('settings', []);
        $saved = 0;

        foreach ($def['fields'] as $field) {
            $key = $field['key'];

            if ($field['type'] === 'boolean') {
                $svc->set($key, $r->boolean("settings.$key"), 'boolean', $def['group']);
                $saved++;

                continue;
            }

            if (! array_key_exists($key, $posted)) {
                // An empty password field must not wipe a stored secret.
                if (($field['type'] ?? '') === 'secret' && ($posted[$key] ?? null) === null) {
                    continue;
                }

                continue;
            }

            $value = $posted[$key];
            $type = $field['type'] === 'number' ? 'number' : ($field['type'] === 'textarea' ? 'text' : ($field['type'] === 'secret' ? 'secret' : 'text'));

            // Keep the existing secret when the field was left blank.
            if ($type === 'secret' && ($value === null || $value === '')) {
                continue;
            }

            $svc->set($key, $value, $type, $def['group']);
            $saved++;
        }

        $this->audit('update_settings', null, $r);

        return back()->with('ok', "Saved {$saved} {$def['label']} setting(s)");
    }

    /** Raw key/value editor for settings outside the tabbed form. */
    public function raw()
    {
        return view('admin.settings.raw', [
            'settings' => \App\Models\Setting::orderBy('group')->orderBy('key')->get(),
            'groups' => \App\Models\Setting::select('group')->distinct()->orderBy('group')->pluck('group'),
        ]);
    }

    public function saveRaw(Request $r, SettingService $svc)
    {
        foreach ((array) $r->input('settings', []) as $key => $val) {
            $row = \App\Models\Setting::where('key', $key)->first();
            $svc->set($key, $val, $row->type ?? 'text', $row->group ?? 'general');
        }

        if ($extra = $r->input('new_key')) {
            $svc->set($extra, $r->input('new_value'), $r->input('new_type', 'text'), $r->input('new_group', 'general'));
        }

        $this->audit('update_settings_raw', null, $r);

        return back()->with('ok', 'Settings saved');
    }
}
