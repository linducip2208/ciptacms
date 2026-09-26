<?php

use App\Http\Controllers\Admin\AppearanceController;
use App\Http\Controllers\Admin\CmsController;
use App\Http\Controllers\Admin\DataBuilderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeveloperController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PluginController;
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SystemAdminController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\UpdateCenterController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WebhookController;
use App\Http\Controllers\Admin\LicenseController;
use App\Http\Controllers\Admin\WhiteLabelController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(['web', 'auth'])->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [SystemAdminController::class, 'search'])->name('search');

    // ---- Generic resources -------------------------------------------
    Route::get('/r/{resource}', [ResourceController::class, 'index'])->name('resource.index');
    Route::get('/r/{resource}/create', [ResourceController::class, 'create'])->name('resource.create');
    Route::post('/r/{resource}', [ResourceController::class, 'store'])->name('resource.store');
    Route::get('/r/{resource}/{id}/edit', [ResourceController::class, 'edit'])->name('resource.edit');
    Route::put('/r/{resource}/{id}', [ResourceController::class, 'update'])->name('resource.update');
    Route::delete('/r/{resource}/{id}', [ResourceController::class, 'destroy'])->name('resource.destroy');
    Route::post('/r/{resource}/bulk', [ResourceController::class, 'bulk'])->name('resource.bulk');
    Route::get('/r/{resource}/export', [ResourceController::class, 'export'])->name('resource.export');

    // ---- Users & access control --------------------------------------
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->name('permissions.update');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy');
    Route::get('/permissions/groups', [PermissionController::class, 'groups'])->name('permissions.groups');
    Route::post('/permissions/groups', [PermissionController::class, 'storeGroup'])->name('permissions.groups.store');
    Route::put('/permissions/groups/{group}', [PermissionController::class, 'updateGroup'])->name('permissions.groups.update');
    Route::delete('/permissions/groups/{group}', [PermissionController::class, 'destroyGroup'])->name('permissions.groups.destroy');

    // ---- Menus (database driven) -------------------------------------
    Route::get('/menus', [\App\Http\Controllers\Admin\MenuController::class, 'index'])->name('menus.index');
    Route::get('/menus/create', [\App\Http\Controllers\Admin\MenuController::class, 'create'])->name('menus.create');
    Route::post('/menus', [\App\Http\Controllers\Admin\MenuController::class, 'store'])->name('menus.store');
    Route::get('/menus/{menu}/edit', [\App\Http\Controllers\Admin\MenuController::class, 'edit'])->name('menus.edit');
    Route::put('/menus/{menu}', [\App\Http\Controllers\Admin\MenuController::class, 'update'])->name('menus.update');
    Route::delete('/menus/{menu}', [\App\Http\Controllers\Admin\MenuController::class, 'destroy'])->name('menus.destroy');
    Route::post('/menus/reorder', [\App\Http\Controllers\Admin\MenuController::class, 'reorder'])->name('menus.reorder');

    // ---- Modules, plugins, themes ------------------------------------
    Route::get('/modules', [ModuleController::class, 'index'])->name('modules.index');
    Route::post('/modules/{slug}/{action}', [ModuleController::class, 'action'])->name('modules.action');
    Route::get('/plugins', [PluginController::class, 'index'])->name('plugins.index');
    Route::post('/plugins/{slug}/{action}', [PluginController::class, 'action'])->name('plugins.action');
    Route::get('/plugins/settings', [PluginController::class, 'settings'])->name('plugins.settings');
    Route::post('/plugins/settings', [PluginController::class, 'saveSettings'])->name('plugins.settings.save');

    Route::get('/themes', [ThemeController::class, 'index'])->name('themes.index');
    Route::post('/themes/{slug}/activate', [ThemeController::class, 'activate'])->name('themes.activate');
    Route::post('/themes/{slug}/deactivate', [ThemeController::class, 'deactivate'])->name('themes.deactivate');
    Route::post('/themes/settings', [ThemeController::class, 'settings'])->name('themes.settings');
    Route::get('/themes/customize', [ThemeController::class, 'customize'])->name('themes.customize');
    Route::post('/themes/customize', [ThemeController::class, 'saveCustomize'])->name('themes.customize.save');

    // ---- Appearance ---------------------------------------------------
    Route::get('/appearance/header', [AppearanceController::class, 'header'])->name('appearance.header');
    Route::post('/appearance/header', [AppearanceController::class, 'saveHeader'])->name('appearance.header.save');
    Route::get('/appearance/footer', [AppearanceController::class, 'footer'])->name('appearance.footer');
    Route::post('/appearance/footer', [AppearanceController::class, 'saveFooter'])->name('appearance.footer.save');
    Route::get('/appearance/homepage', [AppearanceController::class, 'homepage'])->name('appearance.homepage');
    Route::post('/appearance/homepage', [AppearanceController::class, 'saveHomepage'])->name('appearance.homepage.save');
    Route::get('/appearance/custom-code', [AppearanceController::class, 'customCode'])->name('appearance.custom-code');
    Route::post('/appearance/custom-code', [AppearanceController::class, 'saveCustomCode'])->name('appearance.custom-code.save');
    Route::get('/widgets', [AppearanceController::class, 'widgets'])->name('widgets');
    Route::post('/widgets', [AppearanceController::class, 'saveWidget'])->name('widgets.store');
    Route::put('/widgets/{widget}', [AppearanceController::class, 'saveWidget'])->name('widgets.update');
    Route::delete('/widgets/{widget}', [AppearanceController::class, 'destroyWidget'])->name('widgets.destroy');
    Route::post('/widgets/reorder', [AppearanceController::class, 'reorderWidgets'])->name('widgets.reorder');

    // ---- Media --------------------------------------------------------
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/folders', [MediaController::class, 'folders'])->name('media.folders');
    Route::post('/media/folders', [MediaController::class, 'storeFolder'])->name('media.folders.store');
    Route::put('/media/folders/{folder}', [MediaController::class, 'updateFolder'])->name('media.folders.update');
    Route::delete('/media/folders/{folder}', [MediaController::class, 'destroyFolder'])->name('media.folders.destroy');
    Route::get('/media/storage', [MediaController::class, 'storage'])->name('media.storage');
    Route::put('/media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::post('/media/{media}/reprocess', [MediaController::class, 'reprocess'])->name('media.reprocess');
    Route::post('/media/{media}/restore', [MediaController::class, 'restore'])->name('media.restore');
    Route::get('/media/{media}/thumb/{variant}', [MediaController::class, 'thumb'])->name('media.thumb');
    Route::post('/uploads/presign', [\App\Http\Controllers\Admin\UploadController::class, 'presign'])->name('uploads.presign');
    Route::post('/uploads/confirm', [\App\Http\Controllers\Admin\UploadController::class, 'confirm'])->name('uploads.confirm');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');

    // ---- CMS ----------------------------------------------------------
    Route::get('/cms/pages', [CmsController::class, 'pages'])->name('cms.pages.index');
    Route::get('/cms/pages/create', [CmsController::class, 'pageForm'])->name('cms.pages.create');
    Route::get('/cms/pages/{page}/edit', [CmsController::class, 'pageForm'])->name('cms.pages.edit');
    Route::post('/cms/pages/{id?}', [CmsController::class, 'pageSave'])->name('cms.pages.save');
    Route::post('/cms/pages/{page}/duplicate', [CmsController::class, 'duplicatePage'])->name('cms.pages.duplicate');
    Route::post('/cms/pages/{page}/trash', [CmsController::class, 'trashPage'])->name('cms.pages.trash');
    Route::post('/cms/pages/{page}/restore', [CmsController::class, 'restorePage'])->name('cms.pages.restore');
    Route::post('/cms/pages/{page}/revision', [CmsController::class, 'saveRevision'])->name('cms.pages.revision');
    Route::get('/cms/pages/{page}/preview', [CmsController::class, 'previewPage'])->name('cms.pages.preview');

    Route::get('/cms/posts', [CmsController::class, 'posts'])->name('cms.posts.index');
    Route::get('/cms/posts/create', [CmsController::class, 'postForm'])->name('cms.posts.create');
    Route::get('/cms/posts/{post}/edit', [CmsController::class, 'postForm'])->name('cms.posts.edit');
    Route::post('/cms/posts/{post?}', [CmsController::class, 'postSave'])->name('cms.posts.save');
    Route::post('/cms/posts/{post}/trash', [CmsController::class, 'trashPost'])->name('cms.posts.trash');
    Route::post('/cms/posts/{post}/restore', [CmsController::class, 'restorePost'])->name('cms.posts.restore');

    Route::get('/cms/revisions', [CmsController::class, 'revisions'])->name('cms.revisions');
    Route::post('/cms/revisions/{revision}/restore', [CmsController::class, 'restoreRevision'])->name('cms.revisions.restore');
    Route::delete('/cms/revisions/{revision}', [CmsController::class, 'destroyRevision'])->name('cms.revisions.destroy');
    Route::get('/cms/trash', [CmsController::class, 'trash'])->name('cms.trash');
    Route::delete('/cms/trash/{type}/{id}', [CmsController::class, 'forceDelete'])->name('cms.trash.force');
    Route::get('/cms/trash/{type}/{id}', [CmsController::class, 'emptyTrash'])->name('cms.trash.empty');

    Route::get('/cms/sections', [CmsController::class, 'sections'])->name('cms.sections.index');
    Route::get('/cms/components', [CmsController::class, 'components'])->name('cms.components.index');
    Route::get('/cms/blocks', [CmsController::class, 'blocks'])->name('cms.blocks.index');
    Route::post('/cms/blocks', [CmsController::class, 'saveBlock'])->name('cms.blocks.store');
    Route::put('/cms/blocks/{block}', [CmsController::class, 'saveBlock'])->name('cms.blocks.update');
    Route::delete('/cms/blocks/{block}', [CmsController::class, 'destroyBlock'])->name('cms.blocks.destroy');
    Route::get('/cms/templates', [CmsController::class, 'templates'])->name('cms.templates.index');
    Route::post('/cms/templates', [CmsController::class, 'saveTemplate'])->name('cms.templates.store');
    Route::put('/cms/templates/{template}', [CmsController::class, 'saveTemplate'])->name('cms.templates.update');
    Route::delete('/cms/templates/{template}', [CmsController::class, 'destroyTemplate'])->name('cms.templates.destroy');
    Route::post('/cms/templates/{template}/apply', [CmsController::class, 'applyTemplate'])->name('cms.templates.apply');

    Route::get('/cms/comments', [CmsController::class, 'comments'])->name('cms.comments.index');
    Route::post('/cms/comments/{comment}/{status}', [CmsController::class, 'moderate'])->name('cms.comments.moderate');
    Route::post('/cms/comments/bulk', [CmsController::class, 'bulkComments'])->name('cms.comments.bulk');
    Route::get('/cms/comments/word-filter', [CmsController::class, 'wordFilter'])->name('cms.comments.word-filter');
    Route::post('/cms/comments/word-filter', [CmsController::class, 'saveWordFilter'])->name('cms.comments.word-filter.store');
    Route::delete('/cms/comments/word-filter/{wordFilter}', [CmsController::class, 'destroyWordFilter'])->name('cms.comments.word-filter.destroy');
    Route::get('/cms/comments/reports', [CmsController::class, 'commentReports'])->name('cms.comments.reports');
    Route::post('/cms/comments/reports/{report}/status', [CmsController::class, 'updateCommentReport'])->name('cms.comments.reports.status');

    // ---- Form builder -------------------------------------------------
    Route::get('/cms/forms', [CmsController::class, 'forms'])->name('cms.forms.index');
    Route::post('/cms/forms', [CmsController::class, 'saveForm'])->name('cms.forms.store');
    Route::put('/cms/forms/{form}', [CmsController::class, 'saveForm'])->name('cms.forms.update');
    Route::delete('/cms/forms/{form}', [CmsController::class, 'destroyForm'])->name('cms.forms.destroy');
    Route::get('/cms/forms/{form}', [CmsController::class, 'formBuilder'])->name('cms.forms.builder');
    Route::post('/cms/forms/{form}/fields', [CmsController::class, 'saveField'])->name('cms.forms.fields.store');
    Route::put('/cms/forms/{form}/fields/{field}', [CmsController::class, 'saveField'])->name('cms.forms.fields.update');
    Route::delete('/cms/forms/{form}/fields/{field}', [CmsController::class, 'destroyField'])->name('cms.forms.fields.destroy');
    Route::post('/cms/forms/{form}/fields/reorder', [CmsController::class, 'reorderFields'])->name('cms.forms.fields.reorder');
    Route::get('/cms/form-fields', [CmsController::class, 'formFields'])->name('cms.form-fields.index');
    Route::get('/cms/submissions', [CmsController::class, 'submissions'])->name('cms.submissions.index');
    Route::get('/cms/submissions/{submission}', [CmsController::class, 'showSubmission'])->name('cms.submissions.show');
    Route::delete('/cms/submissions/{submission}', [CmsController::class, 'destroySubmission'])->name('cms.submissions.destroy');
    Route::get('/cms/submissions-export', [CmsController::class, 'exportSubmissions'])->name('cms.submissions.export');
    Route::get('/cms/form-notifications', [CmsController::class, 'formNotifications'])->name('cms.form-notifications.index');
    Route::post('/cms/form-notifications', [CmsController::class, 'saveFormNotification'])->name('cms.form-notifications.store');
    Route::delete('/cms/form-notifications/{template}', [CmsController::class, 'destroyFormNotification'])->name('cms.form-notifications.destroy');
    Route::get('/cms/form-spam', [CmsController::class, 'formSpam'])->name('cms.form-spam.index');
    Route::post('/cms/form-spam', [CmsController::class, 'saveFormSpam'])->name('cms.form-spam.save');

    // ---- Data builder -------------------------------------------------
    Route::get('/cms/content-types', [DataBuilderController::class, 'index'])->name('cms.types.index');
    Route::get('/cms/content-types/create', [DataBuilderController::class, 'create'])->name('cms.types.create');
    Route::post('/cms/content-types', [DataBuilderController::class, 'store'])->name('cms.types.store');
    Route::post('/cms/content-types/{contentType}/fields/save', [DataBuilderController::class, 'storeField'])->name('cms.types.save');
    Route::get('/cms/content-types/{contentType}/edit', [DataBuilderController::class, 'edit'])->name('cms.types.edit');
    Route::put('/cms/content-types/{contentType}', [DataBuilderController::class, 'update'])->name('cms.types.update');
    Route::delete('/cms/content-types/{contentType}', [DataBuilderController::class, 'destroy'])->name('cms.types.destroy');
    Route::get('/cms/content-types/fields', [DataBuilderController::class, 'fields'])->name('cms.types.fields');
    Route::get('/cms/content-types/relations', [DataBuilderController::class, 'relations'])->name('cms.types.relations');
    Route::post('/cms/content-types/{contentType}/relations', [DataBuilderController::class, 'storeRelation'])->name('cms.types.relations.store');
    Route::put('/cms/content-types/{contentType}/relations/{name}', [DataBuilderController::class, 'updateRelation'])->name('cms.types.relations.update');
    Route::delete('/cms/content-types/{contentType}/relations/{name}', [DataBuilderController::class, 'destroyRelation'])->name('cms.types.relations.destroy');
    Route::post('/cms/content-types/{contentType}/fields', [DataBuilderController::class, 'storeField'])->name('cms.types.fields.store');
    Route::put('/cms/content-types/{contentType}/fields/{field}', [DataBuilderController::class, 'updateField'])->name('cms.types.fields.update');
    Route::delete('/cms/content-types/{contentType}/fields/{field}', [DataBuilderController::class, 'destroyField'])->name('cms.types.fields.destroy');
    Route::get('/cms/records', [DataBuilderController::class, 'records'])->name('cms.records');
    Route::get('/cms/records/{contentType}', [DataBuilderController::class, 'recordList'])->name('cms.records.list');
    Route::get('/cms/records/{contentType}/create', [DataBuilderController::class, 'recordForm'])->name('cms.records.create');
    Route::post('/cms/records/{contentType}', [DataBuilderController::class, 'storeRecord'])->name('cms.records.store');
    Route::get('/cms/records/{contentType}/{id}/edit', [DataBuilderController::class, 'recordForm'])->name('cms.records.edit');
    Route::put('/cms/records/{contentType}/{id}', [DataBuilderController::class, 'updateRecord'])->name('cms.records.update');
    Route::delete('/cms/records/{contentType}/{id}', [DataBuilderController::class, 'destroyRecord'])->name('cms.records.destroy');
    Route::post('/cms/records/{contentType}/bulk', [DataBuilderController::class, 'bulkRecords'])->name('cms.records.bulk');
    Route::get('/cms/import', [DataBuilderController::class, 'importForm'])->name('cms.import');
    Route::post('/cms/import', [DataBuilderController::class, 'import'])->name('cms.import.run');
    Route::get('/cms/export', [DataBuilderController::class, 'exportForm'])->name('cms.export');
    Route::get('/cms/export/run', [DataBuilderController::class, 'exportRun'])->name('cms.export.run');

    // ---- Workflow -----------------------------------------------------
    Route::get('/cms/workflows', [CmsController::class, 'workflows'])->name('cms.workflows.index');
    Route::get('/cms/workflows/create', [CmsController::class, 'workflowForm'])->name('cms.workflows.create');
    Route::post('/cms/workflows', [CmsController::class, 'workflowSave'])->name('cms.workflows.save');
    Route::get('/cms/workflows/{workflow}/edit', [CmsController::class, 'workflowForm'])->name('cms.workflows.edit');
    Route::put('/cms/workflows/{workflow}', [CmsController::class, 'workflowSave'])->name('cms.workflows.update');
    Route::delete('/cms/workflows/{workflow}', [CmsController::class, 'destroyWorkflow'])->name('cms.workflows.destroy');
    Route::get('/cms/workflows/triggers', [CmsController::class, 'workflowTriggers'])->name('cms.workflows.triggers');
    Route::get('/cms/workflows/conditions', [CmsController::class, 'workflowConditions'])->name('cms.workflows.conditions');
    Route::get('/cms/workflows/actions', [CmsController::class, 'workflowActions'])->name('cms.workflows.actions');
    Route::get('/cms/workflows/runs', [CmsController::class, 'workflowRuns'])->name('cms.workflows.runs');
    Route::get('/cms/workflows/runs/{run}', [CmsController::class, 'showWorkflowRun'])->name('cms.workflows.runs.show');
    Route::post('/cms/workflows/runs/{run}/retry', [CmsController::class, 'retryWorkflowRun'])->name('cms.workflows.runs.retry');

    // ---- Webhooks -----------------------------------------------------
    Route::get('/cms/webhooks', [WebhookController::class, 'index'])->name('cms.webhooks.index');
    Route::post('/cms/webhooks', [WebhookController::class, 'store'])->name('cms.webhooks.store');
    Route::put('/cms/webhooks/{webhook}', [WebhookController::class, 'update'])->name('cms.webhooks.update');
    Route::delete('/cms/webhooks/{webhook}', [WebhookController::class, 'destroy'])->name('cms.webhooks.destroy');
    Route::post('/cms/webhooks/{webhook}/test', [WebhookController::class, 'test'])->name('cms.webhooks.test');
    Route::post('/cms/webhooks/{webhook}/rotate-secret', [WebhookController::class, 'rotateSecret'])->name('cms.webhooks.rotate-secret');
    Route::get('/cms/webhooks/logs', [WebhookController::class, 'logs'])->name('cms.webhooks.logs');
    Route::post('/cms/webhooks/logs/{log}/retry', [WebhookController::class, 'retryLog'])->name('cms.webhooks.logs.retry');
    Route::get('/cms/webhooks/incoming', [WebhookController::class, 'incoming'])->name('cms.webhooks.incoming');
    Route::get('/cms/webhooks/incoming/{key}', [WebhookController::class, 'incomingDetail'])->name('cms.webhooks.incoming.detail');

    // ---- SEO ----------------------------------------------------------
    Route::get('/cms/seo', [SeoController::class, 'index'])->name('seo.index');
    Route::get('/cms/seo/sitemap', [SeoController::class, 'sitemap'])->name('seo.sitemap');
    Route::get('/cms/seo/robots', [SeoController::class, 'robots'])->name('seo.robots');
    Route::get('/cms/seo/redirects', [SeoController::class, 'redirects'])->name('seo.redirects');
    Route::post('/cms/seo/redirects', [SeoController::class, 'saveRedirect'])->name('seo.redirects.store');
    Route::delete('/cms/seo/redirects/{redirect}', [SeoController::class, 'destroyRedirect'])->name('seo.redirects.destroy');
    Route::get('/cms/seo/schema', [SeoController::class, 'schema'])->name('seo.schema');
    Route::get('/cms/seo/{type}/{id}/edit', [SeoController::class, 'edit'])->name('seo.edit');
    Route::put('/cms/seo/{type}/{id}', [SeoController::class, 'update'])->name('seo.update');

    // ---- SaaS: tenants, plans, subscriptions, usage -------------------
    Route::get('/saas/tenants', [\App\Http\Controllers\Admin\SaasController::class, 'tenants'])->name('saas.tenants');
    Route::post('/saas/tenants', [\App\Http\Controllers\Admin\SaasController::class, 'storeTenant'])->name('saas.tenants.store');
    Route::put('/saas/tenants/{tenant}', [\App\Http\Controllers\Admin\SaasController::class, 'updateTenant'])->name('saas.tenants.update');
    Route::get('/saas/plans', [\App\Http\Controllers\Admin\SaasController::class, 'plans'])->name('saas.plans');
    Route::post('/saas/plans', [\App\Http\Controllers\Admin\SaasController::class, 'storePlan'])->name('saas.plans.store');
    Route::get('/saas/features', [\App\Http\Controllers\Admin\SaasController::class, 'features'])->name('saas.features');
    Route::get('/saas/subscriptions', [\App\Http\Controllers\Admin\SaasController::class, 'subscriptions'])->name('saas.subscriptions');
    Route::get('/saas/usage', [\App\Http\Controllers\Admin\SaasController::class, 'usage'])->name('saas.usage');
    Route::get('/saas/domains', [\App\Http\Controllers\Admin\SaasController::class, 'domains'])->name('saas.domains');
    Route::get('/saas/billing', [\App\Http\Controllers\Admin\SaasController::class, 'billing'])->name('saas.billing');
    Route::get('/gateways', [\App\Http\Controllers\Admin\SaasController::class, 'gateways'])->name('gateways');
    Route::post('/gateways', [\App\Http\Controllers\Admin\SaasController::class, 'saveGateways'])->name('gateways.save');

    // Legacy short paths kept so bookmarks and older links keep working.
    Route::get('/tenants', fn () => redirect()->route('admin.saas.tenants'));
    Route::get('/plans', fn () => redirect()->route('admin.saas.plans'));

    // ---- License ------------------------------------------------------
    Route::get('/license', [LicenseController::class, 'index'])->name('licenses.index');
    Route::post('/license', [LicenseController::class, 'store'])->name('licenses.store');
    Route::put('/license/{license}', [LicenseController::class, 'update'])->name('licenses.update');
    Route::delete('/license/{license}', [LicenseController::class, 'destroy'])->name('licenses.destroy');
    Route::get('/license/install', [LicenseController::class, 'index'])->name('licenses.install');
    Route::post('/license/install', [LicenseController::class, 'install'])->name('licenses.install.run');
    Route::post('/license/uninstall', [LicenseController::class, 'uninstall'])->name('licenses.uninstall');
    Route::get('/license/{license}/activations', [LicenseController::class, 'activations'])->name('licenses.activations');
    Route::post('/license/{license}/activations/{domain}/revoke', [LicenseController::class, 'revokeActivation'])->name('licenses.activations.revoke');

    // ---- White label --------------------------------------------------
    Route::get('/white-label', [WhiteLabelController::class, 'index'])->name('whitelabel.index');
    Route::get('/white-label/{section}', [WhiteLabelController::class, 'section'])->name('whitelabel.section');
    Route::post('/white-label/{section}', [WhiteLabelController::class, 'save'])->name('whitelabel.save');
    Route::get('/white-label/domains/create', [WhiteLabelController::class, 'createDomain'])->name('whitelabel.domains.create');
    Route::post('/white-label/domains', [WhiteLabelController::class, 'storeDomain'])->name('whitelabel.domains.store');
    Route::delete('/white-label/domains/{domain}', [WhiteLabelController::class, 'destroyDomain'])->name('whitelabel.domains.destroy');
    Route::post('/white-label/domains/{domain}/primary', [WhiteLabelController::class, 'makePrimaryDomain'])->name('whitelabel.domains.primary');

    // ---- Notifications ------------------------------------------------
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/templates', [NotificationController::class, 'templates'])->name('notifications.templates');
    Route::post('/notifications/templates', [NotificationController::class, 'storeTemplate'])->name('notifications.templates.store');
    Route::put('/notifications/templates/{template}', [NotificationController::class, 'storeTemplate'])->name('notifications.templates.update');
    Route::delete('/notifications/templates/{template}', [NotificationController::class, 'destroyTemplate'])->name('notifications.templates.destroy');
    Route::get('/notifications/deliveries', [NotificationController::class, 'deliveries'])->name('notifications.deliveries');
    Route::post('/notifications/deliveries/{delivery}/retry', [NotificationController::class, 'retryDelivery'])->name('notifications.deliveries.retry');
    Route::post('/notifications/test', [NotificationController::class, 'sendTest'])->name('notifications.test');

    // ---- Security -----------------------------------------------------
    Route::get('/security/2fa', [SecurityController::class, 'twoFactor'])->name('security.2fa');
    Route::post('/security/2fa/enable', [SecurityController::class, 'twoFactorEnable'])->name('2fa.enable');
    Route::post('/security/2fa/disable', [SecurityController::class, 'twoFactorDisable'])->name('2fa.disable');
    Route::post('/security/2fa/regen', [SecurityController::class, 'twoFactorRegen'])->name('2fa.regen');
    Route::get('/security/sessions', [SecurityController::class, 'sessions'])->name('sessions.index');
    Route::delete('/security/sessions/{id}', [SecurityController::class, 'revokeSession'])->name('sessions.revoke');
    Route::post('/security/sessions/logout-others', [SecurityController::class, 'revokeOthers'])->name('sessions.others');
    Route::get('/security/login-history', [SecurityController::class, 'loginHistory'])->name('security.login-history');

    // ---- Settings -----------------------------------------------------
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/settings/raw', [SettingsController::class, 'raw'])->name('settings.raw');
    Route::post('/settings/raw', [SettingsController::class, 'saveRaw'])->name('settings.raw.save');
    Route::get('/settings/{tab}', [SettingsController::class, 'tab'])->name('settings.tab');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // ---- Developer ----------------------------------------------------
    Route::get('/api-docs', fn () => view('admin.system.api-docs'))->name('api-docs');
    Route::get('/developer/events', [DeveloperController::class, 'events'])->name('developer.events');
    Route::post('/developer/events/dispatch', [DeveloperController::class, 'dispatchEvent'])->name('developer.events.dispatch');
    Route::get('/developer/hooks', [DeveloperController::class, 'hooks'])->name('developer.hooks');

    // ---- System -------------------------------------------------------
    Route::get('/health', [SystemAdminController::class, 'health'])->name('health');
    Route::get('/info', [SystemAdminController::class, 'info'])->name('info');
    Route::get('/logs', [SystemAdminController::class, 'logs'])->name('system.logs');
    Route::post('/logs/clear', [SystemAdminController::class, 'clearLogs'])->name('system.logs.clear');
    Route::get('/logs/download', [SystemAdminController::class, 'downloadLog'])->name('system.logs.download');
    Route::get('/audits', [SystemAdminController::class, 'audits'])->name('system.audits');
    Route::get('/system/activity', [SystemAdminController::class, 'activity'])->name('system.activity');
    Route::get('/system/schedule', [SystemAdminController::class, 'schedule'])->name('system.schedule');
    Route::get('/system/cache', [SystemAdminController::class, 'cache'])->name('system.cache');
    Route::post('/system/cache', [SystemAdminController::class, 'flushCache'])->name('system.cache.flush');
    Route::post('/system/optimize', [SystemAdminController::class, 'optimize'])->name('system.optimize');
    Route::get('/system/storage', [SystemAdminController::class, 'storage'])->name('system.storage');
    Route::get('/system/database', [SystemAdminController::class, 'database'])->name('system.database');
    Route::get('/system/maintenance', [SystemAdminController::class, 'maintenance'])->name('system.maintenance');
    Route::post('/system/maintenance', [SystemAdminController::class, 'toggleMaintenance'])->name('system.maintenance.toggle');
    Route::post('/system/artisan', [SystemAdminController::class, 'runArtisan'])->name('system.artisan');
    Route::get('/queue', [QueueController::class, 'index'])->name('queue');
    Route::post('/queue/{id}/retry', [QueueController::class, 'retry'])->name('queue.retry');
    Route::post('/queue/flush', [QueueController::class, 'flush'])->name('queue.flush');

    Route::get('/backups', [SystemAdminController::class, 'backups'])->name('backups.index');
    Route::post('/backups', [SystemAdminController::class, 'runBackup'])->name('backups.run');
    Route::delete('/backups/{backup}', [SystemAdminController::class, 'destroyBackup'])->name('backups.destroy');
    Route::get('/backups/restore', [SystemAdminController::class, 'restore'])->name('backups.restore');
    Route::post('/backups/restore', [SystemAdminController::class, 'runRestore'])->name('backups.restore.run');

    // ---- Updates ------------------------------------------------------
    Route::get('/updates', [UpdateCenterController::class, 'index'])->name('updates.index');
    Route::get('/updates/modules', [UpdateCenterController::class, 'modules'])->name('updates.modules');
    Route::get('/updates/plugins', [UpdateCenterController::class, 'plugins'])->name('updates.plugins');
    Route::get('/updates/themes', [UpdateCenterController::class, 'themes'])->name('updates.themes');
    Route::get('/updates/history', [UpdateCenterController::class, 'history'])->name('updates.history');
    Route::post('/updates/check', [UpdateCenterController::class, 'check'])->name('updates.check');
    Route::post('/updates/apply', [UpdateCenterController::class, 'apply'])->name('updates.apply');

    require __DIR__.'/company.php';
});
