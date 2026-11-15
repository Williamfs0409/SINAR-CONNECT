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
membuat akun dan mengatur peran pengguna melalui menu dashboard. Pengguna aktif dapat saling
mengirim pesan pribadi; pesan yang belum dibaca muncul pada lonceng notifikasi dashboard dan
diperbarui otomatis setiap 30 detik. Foto profil dapat diganti melalui menu akun. Foto disimpan
pada penyimpanan privat dan hanya dapat dilihat oleh pengguna yang masuk. Jalankan
`php artisan migrate` untuk membuat tabel siswa, tagihan, transaksi, pesan, dan kolom foto profil.
Tagihan SPP yang belum lunas hanya tampil sebagai tunggakan setelah tanggal jatuh temponya lewat.
Pratinjau dashboard administrator tanpa login tersedia melalui `/dashboard?role=admin`;
pratinjau ini hanya menampilkan data contoh dan tidak mengizinkan perubahan data.

### Membuat akun administrator awal

Atur `ADMIN_BOOTSTRAP_EMAIL` dan kata sandi unik minimal 12 karakter pada `.env` lokal,
lalu jalankan `php artisan config:clear` jika konfigurasi pernah di-cache dan
`php artisan db:seed`. Seeder hanya membuat akun jika email belum dipakai; seeder tidak
mengubah kata sandi atau peran akun yang sudah ada. Hapus nilai `ADMIN_BOOTSTRAP_PASSWORD`
dari `.env` setelah akun berhasil dibuat.

## Teknologi

- Laravel 12
- PHP ^8.2
- Vite
