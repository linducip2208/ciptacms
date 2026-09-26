<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use App\Core\Services\{SettingService,AuditService,MenuService,ModuleManager,PluginManager,ThemeManager,SeoService,MediaService,SearchService,TenantService,LicenseService,BackupService,UpdateService,WorkflowEngine,WebhookDispatcher,ImportExportService,DataBuilderService,HealthService};
class LinduCoreProvider extends ServiceProvider {
    public function register(): void {
        foreach ([SettingService::class,AuditService::class,MenuService::class,ModuleManager::class,PluginManager::class,ThemeManager::class,SeoService::class,MediaService::class,SearchService::class,TenantService::class,LicenseService::class,BackupService::class,UpdateService::class,WorkflowEngine::class,WebhookDispatcher::class,ImportExportService::class,DataBuilderService::class,HealthService::class] as $c) {
            $this->app->singleton($c);
        }
        if (file_exists(app_path('Core/Support/helpers.php'))) require_once app_path('Core/Support/helpers.php');
    }
    public function boot(): void {
        try { app(ModuleManager::class)->syncRegistry(); } catch(\Throwable $e){}
        try { app(PluginManager::class)->syncRegistry(); } catch(\Throwable $e){}
        try { app(ThemeManager::class)->syncRegistry(); } catch(\Throwable $e){}
    }
}
