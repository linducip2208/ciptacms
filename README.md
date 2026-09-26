# Lindu CMS — Platform Aplikasi Reusable (Laravel 13)

> **Read in:** [English](README.en.md) · [العربية](README.ar.md) · [Indonesia](README.id.md)

**Lindu CMS** adalah platform CMS + aplikasi (POS, ERP, Hotel, LMS, Marketplace, SaaS, Jodohku, dsb) di atas **Laravel 13 + PHP 8.3 + MySQL/SQLite**. Core TIDAK PERNAH berisi logika bisnis spesifik — semua fitur aplikasi hidup sebagai **Modules**.

## Fitur Utama / Key Features / الميزات
| ID | EN | AR |
|---|---|---|
| Core Engine, Auth+2FA, RBAC 127 permissions, Menu dinamis DB-driven | Core engine, auth+2FA, RBAC, DB-driven menus | النواة، المصادقة، الصلاحيات، القوائم |
| Module/Plugin/Theme Engine + lifecycle | Module/plugin/theme lifecycle | الوحدات والإضافات والقوالب |
| CMS, Blog, Media queue (thumbs+WebP/AVIF), SEO, Sitemap | CMS/blog/media queue/SEO | المحتوى والوسائط والسيو |
| Page Builder drag-drop + Form/Data/Workflow Builder | Visual builders | منشئ الصفحات والنماذج |
| REST API v1+v2 + OpenAPI + Webhooks HMAC+retry | API + webhooks | API و Webhooks |
| Multi-tenancy, SaaS plans, White-label, License, Update, Backup, Audit | Tenancy/SaaS/license/backup | تعدد المستأجرين والتراخيص |
| Ecommerce, POS, Hotel, LMS, CRM, ERP, Marketplace, Jodohku | Business apps | المتجر ونقاط البيع والفندق |
| PWA-ready, Admin dark/light responsif, Installer `/install` | PWA + admin + installer | تطبيق ويب تقدمي وتنصيب |

## Quickstart
```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
Login: `admin@lindu.local` / `password123` · Docs: `ARCHITECTURE.md API.md DATABASE.md`

Detail penuh lihat file per bahasa di atas. Push repo: https://github.com/linducip2208/ciptacms
