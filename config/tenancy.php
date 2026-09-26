<?php
// Tenant database-ready: single-DB (tenant_id) today, separate-DB tomorrow.
// Untuk mode separate-DB: set LINDU_TENANT_DB=true lalu isi koneksi 'tenant' per tenant di TenantService::connect().
return [
    'mode' => env('LINDU_TENANT_MODE','single'), // single|separate-ready
    'separate_db' => env('LINDU_TENANT_DB', false),
    'connection' => 'tenant',

    /*
    |----------------------------------------------------------------------
    | Base domain
    |----------------------------------------------------------------------
    | Subdomain tenancy (client1.example.com → tenant with subdomain
    | "client1") only resolves when this is set. Leave it empty and only an
    | exact `domain` match on the tenant row is used, which is the right
    | behaviour for a single-tenant or domain-mapped install.
    */
    'base_domain' => env('LINDU_TENANT_BASE_DOMAIN', ''),
];
