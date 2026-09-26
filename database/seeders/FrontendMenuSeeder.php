<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class FrontendMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Rebuild only the public navigation, admin menu is owned by MenuSeeder.
        MenuItem::where('location', 'primary')->delete();

        $items = [
            ['About', 'ti ti-info-circle', '/about', 10],
            ['Services', 'ti ti-tools', '/services', 20],
            ['Products', 'ti ti-package', '/products', 30],
            ['Portfolio', 'ti ti-briefcase', '/portfolio', 40],
            ['Team', 'ti ti-users', '/team', 50],
            ['Testimonials', 'ti ti-star', '/testimonials', 60],
            ['FAQ', 'ti ti-help-circle', '/faq', 70],
            ['Gallery', 'ti ti-photo', '/gallery', 80],
            ['Careers', 'ti ti-briefcase', '/careers', 90],
            ['Clients', 'ti ti-building-skyscraper', '/clients', 100],
            ['Blog', 'ti ti-news', '/blog', 110],
            ['Contact', 'ti ti-mail', '/contact', 120],
        ];

        foreach ($items as [$title, $icon, $url, $sort]) {
            MenuItem::create([
                'location' => 'primary',
                'title' => $title,
                'icon' => $icon,
                'url' => $url,
                'sort_order' => $sort,
                'is_visible' => true,
                'module' => 'company-profile',
            ]);
        }
    }
}
