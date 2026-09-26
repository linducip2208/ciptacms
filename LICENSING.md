# Licensing

**Moved.** The canonical licensing documentation is [LICENSE.md](LICENSE.md).

It covers both mechanisms that ship in the codebase:

1. The built-in license manager — `App\Core\Services\LicenseService`, the
   `licenses` and `license_activations` tables, statuses
   (`active` / `inactive` / `expired` / `suspended` / `banned`), feature
   entitlements, version and support expiry, activation limits, and the
   optional `App\Core\Contracts\LicenseProvider` remote verification seam.
2. The License v3 pairing gate — `App\Services\LicenseClient` +
   `App\Http\Middleware\RequirePair`, the AES-256-GCM `.license.lock` file, the
   RSA signature check and the 24h heartbeat with a 7-day offline grace window.

This file is kept as a pointer so existing links keep working.
