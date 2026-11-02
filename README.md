# SINAR-CONNECT

Aplikasi web berbasis Laravel 12 untuk proyek SINAR-CONNECT.

## Persyaratan

- PHP 8.2 atau lebih baru
- Composer
- Node.js dan npm

## Menjalankan aplikasi

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000` setelah server berjalan. Atur koneksi database dan variabel lain di file `.env` sesuai lingkungan lokal.

## Pengembangan aset

Jalankan `npm run dev` di terminal terpisah selama pengembangan frontend.

## Teknologi

- Laravel 12
- PHP ^8.2
- Vite
