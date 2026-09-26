<?php

namespace Tests\Feature;

use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->get('/')->assertOk();
    }

    /**
     * The public site must render before any content has been created.
     * Nothing here should depend on demo or seeded records.
     */
    public function test_homepage_renders_with_no_content(): void
    {
        $this->seed(SettingSeeder::class);

        $response = $this->get('/')->assertOk();

        $siteName = (string) setting('general.site_name', 'Lindu CMS');
        $response->assertSee(e($siteName), false);
    }
}
