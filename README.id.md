# Lindu CMS — Platform Aplikasi Reusable (Indonesia)

> Tersedia juga dalam [English](README.en.md) · [العربية](README.ar.md) ·
> [Ringkas Indonesia](README.md)

**Lindu CMS** adalah CMS commercial white-label di atas **Laravel 13 + PHP 8.3
+ MySQL/SQLite + Tabler Admin**. Core tidak pernah berisi logika bisnis spesifik —
fitur aplikasi ada sebagai ekstensi folder + manifest di `modules/`, `plugins/`
dan `themes/`.

Dokumentasi ini ditulis dari kode sumber dan memuat bagian **"Known gaps"**
yang menjelaskan apa yang *belum ada*. Baca bagian itu sebelum menjual sebuah
fitur ke pelanggan.

## Yang benar-benar ada

| Area | Isi | Dokumen |
|---|---|---|
| Core | 28 service singleton, 3 provider, middleware, audit log, health check, backup, import/export | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Auth | Login/register/logout/reset, remember-me, 2FA TOTP + backup code, session manager, OAuth, Sanctum | [SECURITY.md](SECURITY.md) |
| RBAC | User, role, permission, group, **127** permission bawaan dalam 5 group, 4 role, middleware `permission:` | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Menu | `menu_items` DB-driven, nesting tak terbatas, filter permission + role di server, modul auto-register | [MENU_ENGINE.md](MENU_ENGINE.md) |
| Extension engine | Module / plugin / theme: discover → registry → activate/deactivate/uninstall | [MODULES.md](MODULES.md) · [PLUGINS.md](PLUGINS.md) · [THEMES.md](THEMES.md) |
| CMS | Pages (draft/published/scheduled/trash/revisi/template), blog + moderasi komentar, media antrean (thumbs 150/300/800 + WebP/AVIF), SEO meta/OG/sitemap/robots/schema | [PAGE_BUILDER.md](PAGE_BUILDER.md) |
| Page builder | Drag-drop HTML5 sungguhan, **21 komponen**, undo/redo 60 langkah, copy/paste/duplicate, simpan-sebagai-blok, template, visibilitas per breakpoint | [PAGE_BUILDER.md](PAGE_BUILDER.md) |
| Form builder | **18 tipe field**, validasi per tipe, 5 lapis anti-spam, `POST /api/v1/forms/{slug}` | [FORM_BUILDER.md](FORM_BUILDER.md) |
| Data builder | Content type runtime, JSON record + mirror tabel `cb_*`, import/export CSV/JSON dengan laporan error per baris | [DATA_BUILDER.md](DATA_BUILDER.md) |
| Workflow | 17 trigger, 13 operator kondisi, 9 aksi, whitelist model, log run | [WORKFLOW.md](WORKFLOW.md) |
| Company profile | 12 tabel `cp_*`, 8 resource CRUD whitelist, 18 route publik | [COMPANY_PROFILE.md](COMPANY_PROFILE.md) |
| API | REST `v1` + `v2` (Bearer, paginasi, filter, sort, search, `?fields=`/`?include=`), 28 resource generik, OpenAPI | [API.md](API.md) |
| Webhook | Keluar (HMAC + antrean + retry + log) dan masuk | [API.md](API.md) |
| Multi-tenant | Tenant, domain, plan, feature flag, kuota usage, subscription (read-only) | [SAAS.md](SAAS.md) |
| White label | 6 section branding, domain kustom, theme customizer → CSS custom properties | [WHITE_LABEL.md](WHITE_LABEL.md) |
| Lisensi | License manager bawaan **dan** License v3 pairing gate (RSA + AES-256-GCM) | [LICENSE.md](LICENSE.md) |
| Update | Backup → migrate → clear cache → catat versi. **Tidak pernah menjalankan kode remote** | [UPDATES.md](UPDATES.md) |
| Installer | `/install` (3 langkah, terkunci setelah dipakai) + `php artisan lindu:install` | [INSTALL.md](INSTALL.md) |
| Ops | `lindu:doctor`, `lindu:backup`, `lindu:search-index`, `webhooks:retry`, monitor antrean, presigned S3 | [DEVELOPMENT.md](DEVELOPMENT.md) |

## Yang belum ada — baca sebelum berpromosi

- **Modul bisnis adalah stub.** `pos`, `hotel`, `lms`, `crm`, `erp`,
  `ecommerce`, `marketplace`, `jodohku` dan `api` hanya berisi `module.json`
  dan satu route placeholder. Yang tersedia adalah **tabel + generic CRUD**,
  bukan aplikasi: tidak ada transaksi POS, cek ketersediaan kamar, runner kuis,
  atau checkout marketplace. Lihat [MODULES.md](MODULES.md).
- **Plugin adalah stub.** `seo-booster`, `webhook-logger` dan
  `whatsapp-bridge` hanya manifest; tidak ada pemuat kode plugin.
- **Layout theme tidak dipakai.** `themes/*/layout.blade.php` tidak pernah
  dirender. `ThemeManager::deactivate()` tidak ada, jadi tombol deactivate
  selalu error.
- **Payment itu seam, bukan billing.** Lima adapter di atas `PaymentGateway`,
  tanpa checkout, rekonsilasi, invoice, prorasi maupun dunning.
- **Visibilitas per breakpoint belum berefek** di halaman publik, dan `layout`
  section tidak dirender. Lihat [PAGE_BUILDER.md](PAGE_BUILDER.md#known-gaps).
- **Multi-tenant belum ter-scope.** Hanya menu dan mirror `cb_*` yang memfilter
  `tenant_id`. Lihat [SAAS.md](SAAS.md).
- **Rate limiter `api`, `webhook-in` dan `login` terdaftar tapi tidak dipakai.**
  Hanya `throttle:form-submit` yang aktif. Lihat
  [SECURITY.md](SECURITY.md#not-enforced--read-this-before-you-sell-it).
- **Update check tanpa endpoint selalu "lokal"** — artinya tidak ada yang
  dicek, bukan "terkini".
- **PWA minimal.** `manifest.webmanifest` ada; `sw.js` hanya `skipWaiting()`
  dan handler `fetch` kosong.
- **Test suite belum hijau.** Jalankan `php artisan test` dan baca gagalnya.

## Instalasi

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

`.env.example` memakai SQLite, jadi tidak perlu server database.
Login: `admin@lindu.local / password123` — **ganti sebelum situs dapat diakses
siapa pun.**

```sh
composer check          # blade-lint + route-lint + test suite
php artisan lindu:doctor
```

## Struktur

```
app/Core/Services/        28 service singleton + Payments/
app/Core/Contracts/       PaymentGateway, LicenseProvider
app/Models/               112 model (100 inti + 12 App\Models\Cp)
config/lindu_admin.php    28 resource generik → CRUD admin + REST API
modules/ plugins/ themes/ ekstensi folder + manifest
database/migrations/      21 file, 126 tabel
tools/                    blade-lint.php, route-lint.php, test.php
```

## Dokumentasi

Mulai dari [ARCHITECTURE.md](ARCHITECTURE.md), lalu:
[INSTALL](INSTALL.md) · [DEVELOPMENT](DEVELOPMENT.md) · [DEPLOYMENT](DEPLOYMENT.md) ·
[PAGE_BUILDER](PAGE_BUILDER.md) · [FORM_BUILDER](FORM_BUILDER.md) ·
[DATA_BUILDER](DATA_BUILDER.md) · [MENU_ENGINE](MENU_ENGINE.md) ·
[WORKFLOW](WORKFLOW.md) · [COMPANY_PROFILE](COMPANY_PROFILE.md) ·
[WHITE_LABEL](WHITE_LABEL.md) · [UPDATES](UPDATES.md) · [LICENSE](LICENSE.md) ·
[API](API.md) · [DATABASE](DATABASE.md) · [MODULES](MODULES.md) ·
[PLUGINS](PLUGINS.md) · [THEMES](THEMES.md) · [SAAS](SAAS.md) ·
[SECURITY](SECURITY.md) · [TROUBLESHOOTING](TROUBLESHOOTING.md)

Sumber: https://github.com/linducip2208/ciptacms
