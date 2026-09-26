<?php
namespace Tests\Feature;
use Tests\TestCase;
class InstallerTest extends TestCase {
    public function test_install_page_renders(): void { $this->get('/install')->assertOk(); }
    public function test_health_endpoint(): void { $this->get('/up')->assertOk(); }
}
