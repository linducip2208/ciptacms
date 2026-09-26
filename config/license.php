<?php

return [

    /*
    |---------------------------------------------------------------------
    | Marketplace server URL
    |---------------------------------------------------------------------
    | The base URL of the whitelabel marketplace where licenses are
    | issued. The pair wizard talks to this server to activate / heartbeat.
    | Override per-environment via .env LICENSE_SERVER_URL=...
    */
    'server_url' => env('LICENSE_SERVER_URL', 'https://whitelabel.co.id'),

    /*
    |---------------------------------------------------------------------
    | Public key path (RSA)
    |---------------------------------------------------------------------
    | Marketplace public key for verifying signed payloads.
    | Embedded in this kit at ship time — do not edit manually.
    */
    'public_key_path' => public_path('marketplace.public.pem'),

    /*
    |---------------------------------------------------------------------
    | License lock file path
    |---------------------------------------------------------------------
    | Where the encrypted activation payload is stored.
    | Stored outside web root, .gitignored, chmod 600.
    */
    'lock_file' => storage_path('app/.license.lock'),

    /*
    |---------------------------------------------------------------------
    | Heartbeat
    |---------------------------------------------------------------------
    | How often the client pings the marketplace to confirm status.
    | Grace = how long to keep working if marketplace is unreachable.
    */
    'heartbeat_interval' => env('LICENSE_HEARTBEAT_INTERVAL', 86400),  // 24h
    'heartbeat_grace'    => env('LICENSE_HEARTBEAT_GRACE', 604800),    // 7d

    /*
    |----------------------------------------------------------------------
    | Enforcement
    |----------------------------------------------------------------------
    | When true, RequirePair middleware locks every route until the app is
    | paired for the current host. It is always bypassed in the `local` and
    | `testing` environments regardless of this flag — a test suite must never
    | be gated behind a licence, and neither should local development.
    |
    | Turn it off only if you are not shipping a licensed product.
    */
    'enforce' => env('LICENSE_ENFORCE', true),

    /*
    |----------------------------------------------------------------------
    | Dev bypass
    |----------------------------------------------------------------------
    | Skip the license check on localhost / .test / .localhost domains.
    |
    | This defaults to FALSE on purpose. A shipped product must not silently
    | lose its pairing protection because an env var was forgotten, and the
    | `local` and `testing` environments are already bypassed automatically
    | (see RequirePair::shouldBypass). Turn it on only while developing.
    */
    'dev_bypass' => env('LICENSE_DEV_BYPASS', false),

    /*
    |---------------------------------------------------------------------
    | HTTP timeout
    |---------------------------------------------------------------------
    */
    'http_timeout' => 10,
];
