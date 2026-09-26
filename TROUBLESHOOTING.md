# Troubleshooting 503 Service Unavailable (Lokal)

> Teks persis "temporarily unable ... maintenance downtime or capacity problems" = halaman default **Apache**, BUKAN dari kode Lindu.

## Kasus nyata: http://ciptacms.test/ → 503
**Penyebab (terbukti dari log):** file vhost `auto.ciptacms.test.conf` dibuat jam 14:26, sedangkan Apache start jam 01:35 — Apache belum kenal hostname itu sehingga request jatuh ke vhost default (`alias/wanode.conf`) yang proxy ke backend Node `127.0.0.1:8891` yang sedang mati:
`AH00957: attempt to connect to 127.0.0.1:8891 failed` → 503.
**Fix:** reload Apache dari Laragon (klik kanan tray → Reload, atau Stop All → Start All). CLI `httpd -k restart` TIDAK bisa (Apache Laragon bukan Windows service).
**RESOLVED 26 Sep 2026:** setelah reload (child baru 64 workers), `ciptacms.test/` 200, `/login` 200 Tabler, tanpa error proxy.

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
