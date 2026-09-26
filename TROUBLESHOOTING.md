# Troubleshooting 503 Service Unavailable (Lokal)

> Teks persis "temporarily unable ... maintenance downtime or capacity problems" = halaman default **Apache (Laragon)**, BUKAN dari kode Lindu (kode tidak pernah return 503 — sudah di-grep).

## Fix cepat (2 menit)
1. Tutup semua `php artisan serve` lama. Jalankan satu saja:
   ```sh
   cd "D:\project laravel\ciptacms"
   php artisan up
   php artisan optimize:clear
   php artisan serve --host=127.0.0.1 --port=8000
   ```
2. Buka `http://127.0.0.1:8000/up` → harus 200. Lalu `http://127.0.0.1:8000/login`.
3. Cek kesehatan: `php artisan lindu:doctor` → harus SEMUA OK.

## Kalau tetap via Laragon vhost (mis. http://ciptacms.test)
1. Laragon Menu → PHP → Version → **8.3**.
2. Pastikan DocumentRoot vhost = `D:\project laravel\ciptacms\public` (bukan root project).
3. Laragon → Stop All → Start All.
4. Cek `storage\logs\laravel.log` 20 baris terakhir untuk error asli.

## Kalau masih 503
Kirim: URL persis yang dibuka + 20 baris akhir `storage\logs\laravel.log` + hasil `php artisan lindu:doctor`.
