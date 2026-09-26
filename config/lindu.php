<?php
return [
    'name' => env('LINDU_NAME', 'Lindu CMS'),
    'version' => '1.0.0',
    'core_version' => '1.0.0',
    'branding' => [
        'hide_branding' => env('LINDU_HIDE_BRANDING', false),
        'footer_text' => env('LINDU_FOOTER', 'Powered by Lindu CMS'),
    ],
    'modules_path' => base_path('modules'),
    'plugins_path' => base_path('plugins'),
    'themes_path' => base_path('themes'),
    'tenant_mode' => env('LINDU_TENANT_MODE', 'single'), // single|multi
    'installer_lock' => storage_path('app/installed'),
    'api' => ['version' => 'v1', 'rate_limit' => 60],
    'media' => [
        'disk' => env('MEDIA_DISK', 'public'),
        'max_upload_mb' => env('MEDIA_MAX_MB', 10),
        'allowed_mimes' => ['jpg','jpeg','png','gif','webp','avif','pdf','mp4','mp3','svg','doc','docx','xls','xlsx','csv','zip'],
        'thumbnails' => [[150,150],[300,300],[800,600]],
    ],
    'seo' => ['site_name' => env('APP_NAME','Lindu CMS'), 'separator' => ' | '],
    'security' => ['max_login_attempts' => 5, 'lockout_minutes' => 15, '2fa_enabled' => false],
    'backup' => ['disk' => 'local', 'keep' => 7],
    'updates' => ['channel' => env('LINDU_UPDATE_CHANNEL','stable'), 'endpoint' => env('LINDU_UPDATE_URL','')],

    /*
     | License verification provider. Leave empty to use the local
     | license file + activations table only. A provider is any class
     | implementing App\Core\Contracts\LicenseProvider.
     */
    'license' => [
        'provider' => env('LINDU_LICENSE_PROVIDER', ''),
    ],

    /*
     | Payment adapters. Each entry maps a gateway key to a class
     | implementing App\Core\Contracts\PaymentGateway. The CMS ships
     | the interface only — wiring a real gateway is module work.
     */
    'payments' => [
        'currency' => env('LINDU_CURRENCY', 'IDR'),
        'adapters' => [
            'xendit'  => \App\Core\Services\Payments\XenditGateway::class,
            'ipaymu'  => \App\Core\Services\Payments\IpaymuGateway::class,
            'tripay'  => \App\Core\Services\Payments\TripayGateway::class,
            'stripe'  => \App\Core\Services\Payments\StripeGateway::class,
            'generic' => \App\Core\Services\Payments\GenericGateway::class,
        ],
    ],
];
