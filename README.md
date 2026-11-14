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

## Dashboard berdasarkan peran

Setelah migrasi, kolom `users.role` menentukan dashboard yang ditampilkan setelah login:
`teacher` untuk guru, `principal` untuk kepala sekolah, dan `parent` untuk orang tua.
Akun lama dan akun baru secara default menggunakan peran `admin` agar perilaku dashboard
administrator tetap terjaga. Tetapkan nilai `role` yang sesuai pada setiap akun melalui
proses pengelolaan akun yang digunakan di lingkungan aplikasi.

Administrator dapat mengelola pendaftaran dan data siswa, membuat tagihan SPP,
mencatat pembayaran dan transaksi tabungan tunai/transfer, melihat tunggakan, serta
membuat akun dan mengatur peran pengguna melalui menu dashboard. Jalankan `php artisan migrate`
untuk membuat tabel siswa, tagihan, dan transaksi. Tagihan SPP yang belum lunas hanya tampil
sebagai tunggakan setelah tanggal jatuh temponya lewat.
Pratinjau dashboard administrator tanpa login tersedia melalui `/dashboard?role=admin`;
pratinjau ini hanya menampilkan data contoh dan tidak mengizinkan perubahan data.

## Teknologi

- Laravel 12
- PHP ^8.2
- Vite
