<?php

namespace Tests\Feature;

use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\FrontendMenuSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function seedSite(): void
    {
        $this->seed([SettingSeeder::class, FrontendMenuSeeder::class, CompanyProfileSeeder::class]);
    }

    public function test_every_public_page_renders(): void
    {
        $this->seedSite();

        foreach ([
            '/', '/about', '/services', '/products', '/portfolio', '/team',
            '/testimonials', '/clients', '/faq', '/gallery', '/careers',
            '/contact', '/blog', '/sitemap.xml', '/robots.txt',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_seeded_content_appears_on_the_public_pages(): void
    {
        $this->seedSite();

        $this->get('/services')
            ->assertOk()
            ->assertSee('Website Development');

        $this->get('/about')
            ->assertOk()
            ->assertSee('Lindu Studio');

        $this->get('/faq')
            ->assertOk()
            ->assertSee('How long does a typical project take?');

        $this->get('/team')
            ->assertOk()
            ->assertSee('Ayu Prameswari');
    }

    public function test_detail_pages_render_for_seeded_records(): void
    {
        $this->seedSite();

        $service = \App\Models\Cp\Service::published()->first();
        $this->get('/services/'.$service->slug)->assertOk()->assertSee($service->title);

        $product = \App\Models\Cp\Product::published()->first();
        $this->get('/products/'.$product->slug)->assertOk()->assertSee($product->title);

        $portfolio = \App\Models\Cp\Portfolio::published()->first();
        $this->get('/portfolio/'.$portfolio->slug)->assertOk()->assertSee($portfolio->title);

        $career = \App\Models\Cp\Career::published()->first();
        $this->get('/careers/'.$career->slug)->assertOk()->assertSee($career->position);
    }

    public function test_draft_content_is_not_publicly_visible(): void
    {
        $this->seedSite();

        $service = \App\Models\Cp\Service::published()->first();
        $service->update(['status' => 'draft']);

        $this->get('/services/'.$service->slug)->assertNotFound();
        $this->get('/services')->assertOk()->assertDontSee($service->title);
    }

    public function test_sitemap_lists_public_urls(): void
    {
        $this->seedSite();

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('/about', $xml);
        $this->assertStringContainsString('/services', $xml);
    }

    public function test_robots_txt_points_at_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap:', false);
    }
}
