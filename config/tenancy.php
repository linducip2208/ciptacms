<?php
// Tenant database-ready: single-DB (tenant_id) today, separate-DB tomorrow.
// Untuk mode separate-DB: set LINDU_TENANT_DB=true lalu isi koneksi 'tenant' per tenant di TenantService::connect().
return [
    'mode' => env('LINDU_TENANT_MODE','single'), // single|separate-ready
    'separate_db' => env('LINDU_TENANT_DB', false),
    'connection' => 'tenant',
];
