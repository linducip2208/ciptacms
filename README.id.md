# Lindu CMS — Platform Aplikasi Reusable (Indonesia)

> Tersedia juga dalam [English](README.en.md) · [العربية](README.ar.md)

Lindu CMS adalah fondasi CMS + aplikasi (Jodohku, POS, ERP, Hotel, LMS, Marketplace, SaaS, Company Profile) berbasis **Laravel 13 + PHP 8.3 + MySQL/SQLite**. **Core tidak boleh berisi logika bisnis spesifik** — semua fitur aplikasi ada di `modules/`.

## Daftar Fitur
### 1. Core Engine
Bootstrap, konfigurasi (`config/lindu.php`, `tenancy.php`), provider, event/listener, job/queue, scheduler, cache, audit log, exception, storage, lokalisasi (id/en), health check, backup-ready, import/export.

### 2. Auth & Keamanan
Login, register, logout, reset password, remember-me, rate-limit, riwayat login, aktivasi/nonaktif akun, **2FA TOTP** (`/admin/security/2fa` + tantangan `/2fa/challenge` + backup codes), manajemen sesi/perangkat, OAuth sosial-ready, token Sanctum, RBAC.

### 3. RBAC
User, role, permission, grup, policy, 127 permission bawaan (view/create/read/update/delete/publish/approve/export/import/manage/configure), middleware `permission`.

### 4. Menu Engine
Berbasis DB, nesting tak terbatas, ikon/route/URL/permission/role/module/badge/urutan/target/visibilitas/meta, admin drag-order, modul auto-register.

### 5-7. Module / Plugin / Theme
Lifecycle discover→install→activate→deactivate→uninstall, tabel registry, 15 modul, 3 plugin (hooks/filters), 2 tema, aktivasi, settings, custom CSS/JS.

### 8-9. CMS + SEO
Pages (draft/published/scheduled/trash/revisi/builder/template), blog (post/kategori/tag/komentar moderasi), media **antrean** (`ProcessMediaJob`: thumbs 150/300/800 + WebP + AVIF via GD), SEO meta/OG/Twitter/sitemap/robots/schema.

### 10-14. Builder + Workflow
**Page Builder drag-drop** (21 blok, visibilitas desktop/tablet/mobile, undo/redo, live preview, reusable blocks), Form builder (19 tipe field, validasi, kondisional, submission, webhook), Data builder (tabel fisik `cb_*` + CRUD/API instan), Workflow trigger→condition→action + log runs.

### 15-17. Notifikasi / API / Webhook
Notifikasi DB+mail + queue; **REST v1+v2** (`/api/v1|v2/`, Bearer, paginasi/filter/sort/search, v2 `?include=&fields=`), OpenAPI di `/api/docs/openapi.json`; webhook masuk/keluar, HMAC, retry job.

### 18-20. Dashboard / Settings / Media
Widget sadar-permission, statistik, aktivitas; settings per grup + secret terenkripsi; manajer media dengan variants/srcset.

### 21-27. Tenancy / SaaS / Lisensi / Update / Backup
Tenant+domain+kuota, plan/subscription/trial, white-label, lisensi (active/inactive/expired/suspended/banned), cek update + backup otomatis, backup, audit log.

### 28-36. Search / Import-Export / Task / Admin / Installer
Search global (abstraksi), CSV export/import, task, admin responsif (dark/light, sidebar, breadcrumb, palette, toast), installer web `/install`, 15 modul bisnis termasuk **Jodohku** (profil/preferensi/like/favorit/blokir/laporan/membership).

### Pembayaran / PWA / Tenant-DB
Adapter Xendit/iPaymu/Tripay/Stripe/Manual (`/admin/gateways` + tombol test), PWA (`manifest.webmanifest` + `sw.js`), siap separate-DB (`config/tenancy.php`).

## Instalasi / Develop / Test
```sh
composer install; cp .env.example .env; php artisan key:generate
php artisan migrate --seed; php artisan storage:link; php artisan serve
php artisan test; php artisan route:list
```
Login default `admin@lindu.local / password123`.
