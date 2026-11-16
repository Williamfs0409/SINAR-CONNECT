@extends('layouts.app')

@section('title', 'Profil Saya — SINAR-CONNECT')
@section('body-class', 'profile-settings-page')

@section('content')
    <div class="profile-settings-shell">
        <header class="profile-settings-header">
            <a class="database-brand" href="{{ route('dashboard') }}">
                <span class="database-brand-mark">S</span>
                <span> <strong>SINAR-CONNECT</strong><small>Portal sekolah</small></span>
            </a>
            <a class="profile-back-link" href="{{ route('dashboard') }}">Kembali ke dashboard</a>
        </header>

        <main class="profile-settings-main">
            <div class="database-page-heading">
                <span class="database-eyebrow">PENGATURAN AKUN</span>
                <h1>Profil Saya</h1>
                <p class="profile-settings-description">Kelola informasi akun dan foto profil Anda.</p>
            </div>

            @if (session('status'))
                <div class="database-flash" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="database-form-errors" role="alert">
                    <strong>Foto belum dapat disimpan:</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @php
                $profileRoleName = [
                    'admin' => 'Administrator',
                    'principal' => 'Kepala Sekolah',
                    'teacher' => 'Guru',
                    'parent' => 'Orang Tua',
                ][$user->role] ?? 'Pengguna';
            @endphp

            <section class="profile-account-card" aria-labelledby="profile-account-heading">
                <div class="profile-account-heading">
                    <h2 id="profile-account-heading">Informasi Akun</h2>
                    <p>Perbarui nama dan email yang digunakan untuk masuk.</p>
                </div>
                <form class="profile-account-form" method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')
                    <label class="profile-account-field" for="profile-name">
                        <span>Nama lengkap</span>
                        <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required autocomplete="name">
                        @error('name')<small class="profile-photo-error">{{ $message }}</small>@enderror
                    </label>
                    <label class="profile-account-field" for="profile-email">
                        <span>Email</span>
                        <input id="profile-email" type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="150" required autocomplete="email">
                        @error('email')<small class="profile-photo-error">{{ $message }}</small>@enderror
                    </label>
                    <div class="profile-account-field profile-account-role">
                        <span>Peran akun</span>
                        <strong>{{ $profileRoleName }}</strong>
                        <small>Peran akun hanya dapat diubah oleh administrator.</small>
                    </div>
                    <button class="database-button database-button-primary" type="submit">Simpan perubahan</button>
                </form>
            </section>

            <section class="profile-photo-card" aria-labelledby="profile-photo-heading">
                <div class="profile-photo-current">
                    @if ($user->profile_photo_path)
                        <img src="{{ route('profile.photo', ['user' => $user->getKey()]) }}" alt="Foto profil {{ $user->name }}">
                    @else
                        <span>{{ mb_substr($user->name, 0, 1) }}</span>
                    @endif
                </div>
                <div class="profile-photo-copy">
                    <h2 id="profile-photo-heading">Foto Profil</h2>
                    <p>Pilih foto baru untuk mengganti avatar akun Anda.</p>
                    <small>JPG, PNG, atau WEBP · Maksimal 5 MB</small>
                </div>

                <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="profile-photo">Pilih foto</label>
                    <input id="profile-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required>
                    @error('photo')<small class="profile-photo-error">{{ $message }}</small>@enderror
                    <button class="database-button database-button-primary" type="submit">Simpan foto profil</button>
                </form>
            </section>
        </main>
    </div>
@endsection
