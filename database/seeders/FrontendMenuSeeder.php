<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Public navigation.
 *
 * The shape an operator would build in Appearance → Menus, seeded so a fresh
 * install demonstrates grouped navigation rather than thirteen loose links.
 * Grouping lives in the data (parent_id), not in the template: the navbar
 * renders whatever tree it is handed.
 */
class FrontendMenuSeeder extends Seeder
{
    public function run(): void
    {
        MenuItem::where('location', 'primary')->delete();

        $add = function (string $title, ?string $icon, ?string $url, array $meta = []) {
            return MenuItem::create([
                'location' => 'primary',
                'title' => $title,
                'icon' => $icon,
                'url' => $url,
                'sort_order' => 0,
                'is_visible' => true,
                'module' => 'company-profile',
                'meta' => $meta ?: null,
            ]);
        };

        $company = $add('Company', 'ti ti-building', null);
        $business = $add('Business', 'ti ti-briefcase', null);
        $resources = $add('Resources', 'ti ti-news', null);

        $child = function ($parent, string $title, string $icon, string $url, int $sort) {
            MenuItem::create([
                'location' => 'primary',
                'parent_id' => $parent->id,
                'title' => $title,
                'icon' => $icon,
                'url' => $url,
                'sort_order' => $sort,
                'is_visible' => true,
                'module' => 'company-profile',
            ]);
        };

        $child($company, 'About', 'ti ti-info-circle', '/about', 10);
        $child($company, 'Team', 'ti ti-users', '/team', 20);
        $child($company, 'Clients', 'ti ti-building-skyscraper', '/clients', 30);
        $child($company, 'Testimonials', 'ti ti-star', '/testimonials', 40);

        $child($business, 'Services', 'ti ti-tools', '/services', 10);
        $child($business, 'Products', 'ti ti-package', '/products', 20);
        $child($business, 'Portfolio', 'ti ti-briefcase', '/portfolio', 30);
        $child($business, 'Careers', 'ti ti-id-badge-2', '/careers', 40);

        $child($resources, 'Blog', 'ti ti-news', '/blog', 10);
        $child($resources, 'FAQ', 'ti ti-help-circle', '/faq', 20);
        $child($resources, 'Gallery', 'ti ti-photo', '/gallery', 30);

        // Contact stays a top-level action, rendered as the CTA button.
        $add('Contact', 'ti ti-mail', '/contact', ['cta' => true]);
    }
}
