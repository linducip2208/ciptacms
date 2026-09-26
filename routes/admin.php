<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{DashboardController,SettingController,MenuController,ModuleController,PluginController,ThemeController,MediaController,RoleController,UserController,ResourceController,SystemController,TenantController,CmsController,PaymentController,SecurityController};

Route::prefix('admin')->middleware(['web','auth'])->name('admin.')->group(function(){
    Route::get('/', [DashboardController::class,'index'])->name('dashboard');
    // Generic resources
    Route::get('/r/{resource}', [ResourceController::class,'index'])->name('resource.index');
    Route::get('/r/{resource}/create', [ResourceController::class,'create'])->name('resource.create');
    Route::post('/r/{resource}', [ResourceController::class,'store'])->name('resource.store');
    Route::get('/r/{resource}/{id}/edit', [ResourceController::class,'edit'])->name('resource.edit');
    Route::put('/r/{resource}/{id}', [ResourceController::class,'update'])->name('resource.update');
    Route::delete('/r/{resource}/{id}', [ResourceController::class,'destroy'])->name('resource.destroy');
    Route::post('/r/{resource}/bulk', [ResourceController::class,'bulk'])->name('resource.bulk');
    Route::get('/r/{resource}/export', [ResourceController::class,'export'])->name('resource.export');

    Route::get('/users', [UserController::class,'index'])->name('users');
    Route::post('/users', [UserController::class,'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class,'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class,'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle', [UserController::class,'toggle'])->name('users.toggle');

    Route::get('/roles', [RoleController::class,'index'])->name('roles');
    Route::post('/roles', [RoleController::class,'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class,'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class,'destroy'])->name('roles.destroy');

    Route::get('/menus', [MenuController::class,'index'])->name('menus');
    Route::post('/menus', [MenuController::class,'store'])->name('menus.store');
    Route::put('/menus/{menu}', [MenuController::class,'update'])->name('menus.update');
    Route::delete('/menus/{menu}', [MenuController::class,'destroy'])->name('menus.destroy');
    Route::post('/menus/reorder', [MenuController::class,'reorder'])->name('menus.reorder');

    Route::get('/modules', [ModuleController::class,'index'])->name('modules');
    Route::post('/modules/{slug}/{action}', [ModuleController::class,'action'])->name('modules.action');
    Route::get('/plugins', [PluginController::class,'index'])->name('plugins');
    Route::post('/plugins/{slug}/{action}', [PluginController::class,'action'])->name('plugins.action');
    Route::get('/themes', [ThemeController::class,'index'])->name('themes');
    Route::post('/themes/{slug}/activate', [ThemeController::class,'activate'])->name('themes.activate');
    Route::post('/themes/settings', [ThemeController::class,'settings'])->name('themes.settings');

    Route::get('/media', [MediaController::class,'index'])->name('media');
    Route::post('/media', [MediaController::class,'store'])->name('media.store');
    Route::post('/media/{media}/reprocess', [MediaController::class,'reprocess'])->name('media.reprocess');
    Route::get('/media/{media}/thumb/{variant}', [MediaController::class,'thumb'])->name('media.thumb');
    Route::post('/uploads/presign', [\App\Http\Controllers\Admin\UploadController::class,'presign'])->name('uploads.presign');
    Route::post('/uploads/confirm', [\App\Http\Controllers\Admin\UploadController::class,'confirm'])->name('uploads.confirm');
    Route::delete('/media/{media}', [MediaController::class,'destroy'])->name('media.destroy');

    Route::get('/security/2fa', [SecurityController::class,'twoFactor'])->name('2fa');
    Route::post('/security/2fa/enable', [SecurityController::class,'twoFactorEnable'])->name('2fa.enable');
    Route::post('/security/2fa/disable', [SecurityController::class,'twoFactorDisable'])->name('2fa.disable');
    Route::post('/security/2fa/regen', [SecurityController::class,'twoFactorRegen'])->name('2fa.regen');
    Route::get('/security/sessions', [SecurityController::class,'sessions'])->name('sessions');
    Route::delete('/security/sessions/{id}', [SecurityController::class,'revokeSession'])->name('sessions.revoke');
    Route::post('/security/sessions/logout-others', [SecurityController::class,'revokeOthers'])->name('sessions.others');

    Route::get('/settings', [SettingController::class,'index'])->name('settings');
    Route::post('/settings', [SettingController::class,'update'])->name('settings.update');

    // CMS
    Route::get('/cms/pages', [CmsController::class,'pages'])->name('cms.pages');
    Route::get('/cms/pages/create', [CmsController::class,'pageForm'])->name('cms.pages.create');
    Route::get('/cms/pages/{page}/edit', [CmsController::class,'pageForm'])->name('cms.pages.edit');
    Route::post('/cms/pages/{id?}', [CmsController::class,'pageSave'])->name('cms.pages.save');
    Route::get('/cms/posts', [CmsController::class,'posts'])->name('cms.posts');
    Route::get('/cms/comments', [CmsController::class,'comments'])->name('cms.comments');
    Route::post('/cms/comments/{comment}/{status}', [CmsController::class,'moderate'])->name('cms.comments.moderate');
    Route::get('/cms/forms', [CmsController::class,'forms'])->name('cms.forms');
    Route::get('/cms/forms/{form}', [CmsController::class,'formBuilder'])->name('cms.forms.builder');
    Route::get('/cms/content-types', [CmsController::class,'contentTypes'])->name('cms.types');
    Route::post('/cms/content-types/{id?}', [CmsController::class,'contentTypeSave'])->name('cms.types.save');
    Route::get('/cms/workflows', [CmsController::class,'workflows'])->name('cms.workflows');
    Route::post('/cms/workflows', [CmsController::class,'workflowSave'])->name('cms.workflows.save');
    Route::get('/cms/webhooks', [CmsController::class,'webhooks'])->name('cms.webhooks');
    Route::get('/cms/seo', [CmsController::class,'seo'])->name('cms.seo');

    // Tenancy / SaaS
    Route::get('/tenants', [TenantController::class,'index'])->name('tenants');
    Route::post('/tenants', [TenantController::class,'store'])->name('tenants.store');
    Route::get('/plans', [TenantController::class,'plans'])->name('plans');
    Route::post('/plans', [TenantController::class,'storePlan'])->name('plans.store');
    Route::get('/licenses', [TenantController::class,'licenses'])->name('licenses');
    Route::post('/licenses', [TenantController::class,'issueLicense'])->name('licenses.issue');
    Route::get('/gateways', [PaymentController::class,'gateways'])->name('gateways');
    Route::post('/gateways', [PaymentController::class,'save'])->name('gateways.save');
    Route::post('/gateways/test', [PaymentController::class,'test'])->name('gateways.test');
    Route::get('/api-docs', fn()=>view('admin.system.api-docs'))->name('api-docs');

    // System
    Route::get('/health', [SystemController::class,'health'])->name('health');
    Route::get('/info', [SystemController::class,'info'])->name('info');
    Route::get('/audits', [SystemController::class,'audits'])->name('audits');
    Route::get('/backups', [SystemController::class,'backups'])->name('backups');
    Route::post('/backups', [SystemController::class,'runBackup'])->name('backups.run');
    Route::get('/updates', [SystemController::class,'updates'])->name('updates');
    Route::get('/search', [SystemController::class,'search'])->name('search');
    Route::get('/queue', [\App\Http\Controllers\Admin\QueueController::class,'index'])->name('queue');
    Route::post('/queue/{id}/retry', [\App\Http\Controllers\Admin\QueueController::class,'retry'])->name('queue.retry');
    Route::post('/queue/flush', [\App\Http\Controllers\Admin\QueueController::class,'flush'])->name('queue.flush');
});
