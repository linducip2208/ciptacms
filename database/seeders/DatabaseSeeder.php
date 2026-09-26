<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([RolesPermissionsSeeder::class, SettingSeeder::class, UserSeeder::class, MenuSeeder::class, TenantLicenseSeeder::class, CmsSeeder::class, CommerceSeeder::class, AppsSeeder::class]);
        try{ app(\App\Core\Services\ModuleManager::class)->syncRegistry(); }catch(\Throwable $e){}
        try{ app(\App\Core\Services\PluginManager::class)->syncRegistry(); }catch(\Throwable $e){}
        try{ app(\App\Core\Services\ThemeManager::class)->syncRegistry(); }catch(\Throwable $e){}
    }
}
