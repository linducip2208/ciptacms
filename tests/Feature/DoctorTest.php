<?php
namespace Tests\Feature;
use Tests\TestCase;
class DoctorTest extends TestCase {
    public function test_doctor_runs(): void {
        $this->artisan('lindu:doctor')->assertOk();
    }
}
