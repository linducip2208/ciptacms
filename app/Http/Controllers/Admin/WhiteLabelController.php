<?php

namespace App\Http\Controllers\Admin;

use App\Models\WhiteLabelDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * White label. Every Lindu-branded string the operator can see is a
 * database-driven setting, read through setting() by the views. Nothing in
 * the layout hard-codes the vendor name.
 */
class WhiteLabelController extends AdminController
{
    public const SECTIONS = [
        'branding' => [
            'label' => 'Branding',
            'description' => 'Site identity shown on the public website.',
            'fields' => [
                ['branding.logo', 'Logo URL', 'image', 'Shown in the site header.'],
                ['branding.favicon', 'Favicon URL', 'image', ''],
                ['branding.og_image', 'Default share image', 'image', 'Fallback OpenGraph image.'],
                ['general.site_name', 'Application name', 'text', 'Used in titles, emails and the admin header.'],
                ['general.tagline', 'Tagline', 'text', ''],
                ['general.footer', 'Footer line', 'text', ''],
                ['branding.primary_color', 'Primary colour', 'color', ''],
                ['branding.secondary_color', 'Secondary colour', 'color', ''],
                ['branding.radius', 'Corner radius', 'text', 'e.g. 12px'],
            ],
        ],
        'login' => [
            'label' => 'Login Branding',
            'description' => 'What the sign-in screen looks like.',
            'fields' => [
                ['branding.login_logo', 'Login logo', 'image', ''],
                ['branding.login_background', 'Login background image', 'image', 'A wide image works best.'],
                ['branding.admin_title', 'Admin panel title', 'text', 'Leave empty to use the application name.'],
            ],
        ],
        'email' => [
            'label' => 'Email Branding',
            'description' => 'Sender identity for every outgoing email.',
            'fields' => [
                ['branding.email_from_name', 'Sender name', 'text', ''],
                ['branding.email_from_address', 'Sender address', 'text', 'Must be a domain you control.'],
                ['email.from_name', 'Transactional from name', 'text', ''],
                ['email.from_address', 'Transactional from address', 'text', ''],
            ],
        ],
        'code' => [
            'label' => 'Custom CSS / JS',
            'description' => 'Injected on every public page.',
            'fields' => [
                ['branding.custom_css', 'Custom CSS', 'code', ''],
                ['branding.custom_js', 'Custom JavaScript', 'code', 'Runs before </body>.'],
                ['branding.custom_head', 'Custom <head>', 'code', ''],
                ['branding.custom_footer', 'Custom footer', 'code', ''],
            ],
        ],
        'errors' => [
            'label' => 'Error Branding',
            'description' => 'What visitors see on 404 and 500 pages.',
            'fields' => [
                ['branding.error_404_title', '404 title', 'text', ''],
                ['branding.error_404_body', '404 message', 'textarea', ''],
                ['branding.error_500_title', '500 title', 'text', ''],
                ['branding.error_500_body', '500 message', 'textarea', ''],
            ],
        ],
        'advanced' => [
            'label' => 'Advanced Branding',
            'description' => 'Vendor credits and support metadata.',
            'fields' => [
                ['branding.footer_branding', 'Footer vendor credit', 'text', 'Leave empty to hide all vendor branding.'],
                ['branding.hide_powered_by', 'Hide "Powered by" everywhere', 'boolean', ''],
                ['branding.support_url', 'Support link', 'text', ''],
                ['branding.docs_url', 'Documentation link', 'text', ''],
            ],
        ],
    ];

    public function index()
    {
        return $this->section('branding');
    }

    public function section(string $section)
    {
        if (! isset(self::SECTIONS[$section])) {
            abort(404, 'Unknown white-label section: '.$section);
        }

        return view('admin.whitelabel.index', [
            'section' => $section,
            'def' => self::SECTIONS[$section],
            'sections' => self::SECTIONS,
            'values' => app(\App\Core\Services\SettingService::class)->all(),
            'domains' => WhiteLabelDomain::orderBy('is_primary', 'desc')->orderBy('domain')->get(),
        ]);
    }

    public function save(Request $r, string $section)
    {
        if (! isset(self::SECTIONS[$section])) {
            abort(404);
        }

        $def = self::SECTIONS[$section];
        $svc = app(\App\Core\Services\SettingService::class);
        $posted = (array) $r->input('settings', []);
        $saved = 0;

        foreach ($def['fields'] as [$key, $label, $type, $help]) {
            if ($type === 'boolean') {
                $svc->set($key, $r->boolean("settings.$key"), 'boolean', Str::before($key, '.'));

                continue;
            }
            if (! array_key_exists($key, $posted)) {
                continue;
            }
            $svc->set($key, $posted[$key], in_array($type, ['textarea', 'code'], true) ? 'text' : 'text', Str::before($key, '.'));
            $saved++;
        }

        $this->audit('update_whitelabel', null, $r);

        return back()->with('ok', "Saved {$def['label']} ({$saved} value(s))");
    }

    public function createDomain()
    {
        return view('admin.whitelabel.domain-form', ['row' => new WhiteLabelDomain]);
    }

    public function storeDomain(Request $r)
    {
        $data = $r->validate([
            'domain' => 'required|string|max:190|unique:white_label_domains,domain',
        ]);

        $data['domain'] = Str::of($data['domain'])
            ->lower()
            ->replaceMatches('#^https?://#', '')
            ->replaceMatches('#/.*$#', '')
            ->replace('www.', '')
            ->toString();

        WhiteLabelDomain::create($data + ['is_primary' => WhiteLabelDomain::count() === 0]);
        $this->audit('create_domain', null, $r);

        return redirect()->route('admin.whitelabel.section', 'branding')->with('ok', "Domain {$data['domain']} added");
    }

    public function destroyDomain(Request $r, WhiteLabelDomain $domain)
    {
        $domain->delete();
        $this->audit('delete_domain', $domain, $r);

        return back()->with('ok', 'Domain removed');
    }

    public function makePrimaryDomain(Request $r, WhiteLabelDomain $domain)
    {
        WhiteLabelDomain::update(['is_primary' => false]);
        $domain->update(['is_primary' => true]);
        $this->audit('primary_domain', $domain, $r);

        return back()->with('ok', "{$domain->domain} is now the primary domain");
    }
}
