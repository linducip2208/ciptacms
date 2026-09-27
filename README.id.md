<div align="center">

# CiptaCMS

**CMS company-profile white-label dan inti aplikasi, dibangun di atas Laravel 13.**

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-ff2d20?logo=laravel&logoColor=white)](https://laravel.com)
[![Tabler](https://img.shields.io/badge/Tabler-1.4-0d6efd?logo=tabler&logoColor=white)](https://tabler.com)
[![Tests](https://img.shields.io/badge/tests-558%20lulus-success)](#pengujian)

[English](README.en.md) · [Bahasa Indonesia](README.id.md) · [العربية](README.ar.md)

</div>

---

## Apa produk ini

CiptaCMS adalah dua produk dalam satu repositori:

1. **CMS company-profile** — situs publik beserta seluruh isinya: page builder, form builder, media, menu engine, blog, FAQ, testimonial, portfolio, karier, SEO.
2. **Inti aplikasi** — engine, admin, dan titik ekstensi tempat produk lain dibangun.

Produk ini dijual kepada perusahaan yang ingin situs sendiri tanpa developer, dan kepada
developer yang ingin CMS core untuk dijadikan dasar aplikasi.

**Ini produk komersial, bukan demo.** Semua angka di bawah diukur langsung dari repositori pada
commit saat ini, dan setiap klaim didukung oleh test. Yang belum selesai dicantumkan di
[Kesenjangan yang diketahui](#kesenjangan-yang-diketahui), bukan disembunyikan.

---

## Fakta terukur

| | |
|---|---|
| Test lulus | **558 / 559** (1 dilewati: permission file `0600` tidak berlaku di Windows) |
| Assertion | **2080** |
| Route HTTP | **391** |
| Komponen page builder | **21** |
| Migrasi | **23** |
| Model Eloquent | **112** |
| Service inti | **30** |
| Komponen UI publik yang dapat dipakai ulang | **17** |
| Build frontend | Vite + Tabler 1.4, di-bundle lokal, **tanpa CDN runtime** |

---

## Fitur

### Situs publik (company profile)

Home · About · Services · Products · Portfolio · Team · Testimonials · Clients · FAQ ·
Gallery · Careers · Blog · Contact — ditambah halaman detail untuk service, product, portfolio,
karier, artikel blog, dan halaman CMS mana pun.

- **Seluruh konten berasal dari database.** Tidak ada yang ditulis mati di halaman publik.
- **Navigasi berasal dari Menu Engine.** Induk menu menjadi dropdown, item bertanda CTA menjadi
  tombol, dan pohon yang sama dipakai navbar desktop, offcanvas mobile, serta footer.
- **Data demo sudah disertakan** di installer dan dapat diubah atau dihapus sepenuhnya dari admin.
- **Form kontak dan lamaran karier** tersimpan ke database dan memberi notifikasi ke admin.
- **Peta, tautan sosial, jam operasional, telepon, WhatsApp** semuanya dari pengaturan company profile.

### Design system

- **Tabler 1.4 adalah fondasi** untuk situs publik maupun admin — bundle yang sama.
- **Branding CMS dijembatankan ke properti CSS milik Tabler sendiri** (`--tblr-primary`,
  `--tblr-border-radius`, `--tblr-font-sans-serif`, dan sejenisnya), sehingga instalasi white-label
  dapat mengganti warna produk tanpa memalsukan Tabler.
- **18 design token** dibaca dari database: warna, font, bobot, radius, lebar container, spasi
  section, tinggi header, warna footer.
- **Mode terang dan gelap**, mengikuti pengaturan theme atau preferensi sistem operasi pengunjung.
- **Satu stylesheet.** Tabler + utility Tailwind + design token, dikompilasi jadi satu berkas.
- **Tanpa CDN runtime.** Gangguan jaringan tidak dapat menjatuhkan situs, admin, maupun halaman login.

### Page builder

21 komponen: heading, text, image, video, button, icon, card, grid, gallery, slider, tabs,
accordion, testimonials, pricing, team, contact, map, form, html, code, dynamic content.

- Drag-and-drop HTML5 sungguhan; field inspector dibangkitkan dari katalog komponen.
- Section dengan visibilitas per breakpoint (desktop / tablet / mobile).
- Undo / redo, copy, paste, duplikat, hapus, urutkan.
- Simpan komponen apa pun sebagai reusable block, pakai lagi di halaman lain.
- Template halaman; penerapan template menyimpan snapshot layout sebelumnya.
- Output setiap komponen di-escape, responsif, dan dirender lewat design system yang sama.

### Form builder

- Builder drag-and-drop; 19 tipe field.
- Validasi, flag required, constraint unique, opsi.
- **Anti-spam berlapis:** honeypot, durasi isi minimum, daftar kata terlarang, rate limit per IP.
- Submission tersimpan di database, bisa dicari dan diekspor di admin.
- Form publik memakai kelas form Tabler yang sama dengan form lain di situs.
- Form bisa disisipkan di halaman mana pun lewat komponen `form` milik page builder.

### Menu engine

Nesting tanpa batas, URL atau named route per item, ikon, permission, badge, target, urutan,
visibilitas, dan metadata bebas. Dua lokasi bawaan: `admin` (sidebar) dan `primary` (publik).
Modul mendeklarasikan menu sendiri lewat manifest.

### Engine theme, plugin, dan module

- **Modules** — folder plus manifest. Ditemukan, diinstal, diaktifkan, dinonaktifkan, dengan
  pengecekan dependensi. **Hanya module yang terinstal *dan* aktif** yang menyambungkan route,
  view, translasi, dan migrasi; tidak ada isi folder yang bisa diakses sebelum operator
  mengaktifkannya.
- **Plugins** — penemuan sama, ditambah kontrak berversi (`PluginInterface`) dengan rantai filter
  dan event hook yang benar-benar jalan.
- **Themes** — design token, customizer, dan aktivasi yang benar-benar mengubah tampilan pengunjung.

### Perpustakaan media

Upload dengan penegakan allow-list MIME, folder, pencarian, filter, thumbnail, varian WebP/AVIF,
metadata, alt text dan caption, trash dan restore. Disk dapat dikonfigurasi; siap S3-compatible.

### SEO

Metadata per halaman dan per artikel, default global, OpenGraph dan Twitter/X card, canonical URL,
JSON-LD untuk organisasi dan breadcrumb, `sitemap.xml` yang dihasilkan langsung, `robots.txt` dari
pengaturan, serta pengelolaan redirect.

### Relasi konten

Content type dapat mendefinisikan relasi satu sama lain — `hasOne`, `hasMany`, `belongsTo`,
`belongsToMany`, `morphOne`, `morphMany` — diselesaikan sesuai permintaan dan tersedia lewat
`?include=` di API. Relasi satu-ke-banyak memakai pivot table sungguhan; tidak ada yang di-resolve
secara eager.

### Role, permission, dan API

- Role, group permission, permission granular, ditegakkan di route, controller, dan API.
- REST API di `/api/v1` dengan token Sanctum, pagination, filter, sorting, pencarian, dan relasi.
- Webhook inbound dan outbound dengan verifikasi HMAC yang **fail closed**.

### Administrasi

Users, roles, permissions, groups, riwayat login, sesi, 2FA, activity log, audit log,
system health, logs, queue, scheduled tasks, cache, storage, database, backup dan restore,
maintenance mode, installer, dan update centre.

### Paket, batas, dan lisensi

Batas paket dan feature flag yang **ditegakkan**, bukan sekadar ditampilkan: batas paket
menghentikan aksi dan mengembalikan HTTP 402, fitur terkunci menolak dengan HTTP 403. Counter
dihitung ulang setiap malam.

Kit lisensi marketplace dengan domain binding, batas aktivasi, kedaluwarsa, suspend, dan
version entitlement.

### Jaring pengaman

- **Wizard instalasi** dengan gerbang requirement yang menolak melanjutkan di host yang tidak
  didukung, dan install lock yang hanya bisa dibuka dari console server.
- **Backup** yang menghasilkan dump SQL asli berkas aplikasi, dengan restore yang mewajibkan
  konfirmasi eksplisit.
- **Update engine** yang mengambil backup, menjalankan migrasi, dan membersihkan cache — serta
  **tidak pernah** mengeksekusi kode remote. Tanpa endpoint update yang dikonfigurasi, ia
  melaporkan "tidak ada yang diperiksa", bukan mengklaim sudah terbaru.

---

## Kebutuhan sistem

PHP 8.3+ dengan `pdo`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`, dan `curl` ·
MySQL 8 / MariaDB 10.6+ atau SQLite 3 · Composer 2 · Node 18+

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Lalu buka `/install` untuk menyelesaikan konfigurasi, atau masuk di `/login` sebagai administrator
bawaan. Petunjuk lengkap di [INSTALL.md](INSTALL.md).

## Dokumentasi

| | |
|---|---|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Struktur codebase |
| [DATABASE.md](DATABASE.md) | Skema dan relasi |
| [INSTALL.md](INSTALL.md) | Instalasi |
| [DEVELOPMENT.md](DEVELOPMENT.md) | Bekerja pada codebase |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Deployment produksi |
| [SECURITY.md](SECURITY.md) | Model keamanan |
| [API.md](API.md) | REST API |
| [PAGE_BUILDER.md](PAGE_BUILDER.md) | Page builder |
| [FORM_BUILDER.md](FORM_BUILDER.md) | Form builder |
| [DATA_BUILDER.md](DATA_BUILDER.md) | Content type dan relasi |
| [MENU_ENGINE.md](MENU_ENGINE.md) | Navigasi |
| [WORKFLOW.md](WORKFLOW.md) | Otomasi |
| [WHITE_LABEL.md](WHITE_LABEL.md) | Rebranding |
| [THEMES.md](THEMES.md) | Theme engine |
| [MODULES.md](MODULES.md) · [PLUGINS.md](PLUGINS.md) | Engine ekstensi |
| [LICENSE.md](LICENSE.md) | Lisensi |
| [UPDATES.md](UPDATES.md) | Update engine |
| [COMPANY_PROFILE.md](COMPANY_PROFILE.md) | Modul company profile |
| [SAAS.md](SAAS.md) · [TROUBLESHOOTING.md](TROUBLESHOOTING.md) | Operations |

## Pengujian

```bash
php tools/blade-lint.php     # setiap template kompilasi ke PHP valid
php tools/route-lint.php     # setiap nama route() di template benar-benar ada
php tools/screenshot.php     # render headless browser + tangkapan layar
composer check               # semuanya di atas plus test suite
```

## Kesenjangan yang diketahui

Disebutkan terbuka, karena produk yang menyembunyikan kesenjangannya tidak bisa dijual dengan jujur:

- **Dua dari tiga plugin bawaan masih stub.** `webhook-logger` dan `whatsapp-bridge` baru punya
  manifest, belum ada implementasi. `seo-booster` sudah jalan. Engine plugin-nya sendiri terbukti;
  dua folder lainnya belum.
- **Belum ada aktivasi lisensi marketplace sungguhan** ke server asli. Jalur kode kit diuji dengan
  pasangan kunci pengganti; aktivasi `whitelabel.co.id` yang sebenarnya belum pernah dilakukan.
- **Aksesibilitas diuji secara struktural, bukan audit penuh.** Alt text, label, ARIA, urutan
  heading, dan kontras warna diuji otomatis. Uji screen reader dan telusur keyboard manual
  belum dijalankan oleh manusia.
- **Cakupan responsif struktural plus tangkapan layar.** Tujuh lebar direkam browser headless, dan
  test memastikan tidak ada elemen lebar tetap yang menimbulkan overflow — tetapi tidak ada baseline
  regresi visual, jadi perubahan berikutnya bisa mengubah tampilan tanpa menggagalkan test.
- **i18n antarmuka admin belum diimplementasikan.** Folder `lang/` ada dan pipeline translasi sudah
  tersambung, tetapi string admin hanya bahasa Inggris.
- **Dua tema disertakan**, salah satunya (`dark-commerce`) belum punya token sendiri selain
  pengaturan mode warna.

## Lisensi

Komersial. Lihat [LICENSE.md](LICENSE.md) untuk ketentuan, aktivasi, dan redistribusi.
