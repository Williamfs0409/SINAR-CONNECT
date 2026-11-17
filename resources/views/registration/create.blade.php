@extends('layouts.app')

@section('title', 'Pendaftaran Siswa Baru — SINAR')
@section('body-class', 'registration-page')

@section('content')
    <main class="registration-shell">
        <header class="registration-header">
            <a class="registration-brand" href="{{ route('home') }}"><img src="{{ asset('images/sinar-school-logo.png') }}" alt=""><strong>SINAR-CONNECT</strong></a>
            <a href="{{ route('login') }}">Sudah punya akun? Masuk</a>
        </header>

        <section class="registration-card">
            @if ($errors->any())
                <div class="registration-errors" role="alert">
                    <strong>Periksa kembali data berikut:</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @if (! $registrationOpen)
                <p class="registration-eyebrow">Informasi pendaftaran</p>
                <h1>Pendaftaran siswa baru</h1>
                <p class="registration-intro" role="status">Pendaftaran siswa baru sedang ditutup. Silakan hubungi pihak sekolah atau kunjungi kembali nanti.</p>
            @else
                <p class="registration-eyebrow">Tahun ajaran {{ $schoolYear->nama }}</p>
                <h1>Pendaftaran siswa baru</h1>
                <p class="registration-intro">Isi data anak dan orang tua/wali. Akun orang tua akan dibuat agar status pendaftaran dapat dipantau.</p>

            <form method="POST" action="{{ route('registration.store') }}" enctype="multipart/form-data">
                @csrf
                <fieldset>
                    <legend>Data calon siswa</legend>
                    <div class="registration-grid">
                        <label>Nama lengkap <input name="student_name" value="{{ old('student_name') }}" maxlength="100" required></label>
                        <label>Nama panggilan <input name="student_nickname" value="{{ old('student_nickname') }}" maxlength="100"></label>
                        <label>Jenis kelamin
                            <select name="student_gender" required>
                                <option value="">Pilih</option>
                                <option value="Laki-Laki" @selected(old('student_gender') === 'Laki-Laki')>Laki-laki</option>
                                <option value="Perempuan" @selected(old('student_gender') === 'Perempuan')>Perempuan</option>
                            </select>
                        </label>
                        <label>Kelompok tujuan
                            <select name="group" required>
                                <option value="">Pilih kelompok</option>
                                @foreach (['KB', 'TK A', 'TK B'] as $group)
                                    <option value="{{ $group }}" @selected(old('group') === $group)>{{ $group }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Tempat lahir <input name="student_birthplace" value="{{ old('student_birthplace') }}" maxlength="100"></label>
                        <label>Tanggal lahir <input type="date" name="student_birthdate" value="{{ old('student_birthdate') }}" max="{{ now()->toDateString() }}" required></label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Data orang tua/wali</legend>
                    <div class="registration-grid">
                        <label>Nama orang tua/wali <input name="guardian_name" value="{{ old('guardian_name') }}" maxlength="100" required></label>
                        <label>Hubungan
                            <select name="guardian_relationship" required>
                                <option value="">Pilih</option>
                                @foreach (['Ayah', 'Ibu', 'Wali'] as $relationship)
                                    <option value="{{ $relationship }}" @selected(old('guardian_relationship') === $relationship)>{{ $relationship }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Nomor HP <input name="guardian_phone" value="{{ old('guardian_phone') }}" maxlength="16" required></label>
                        <label>Nomor Kartu Keluarga (opsional) <input name="family_card_number" value="{{ old('family_card_number') }}" inputmode="numeric" maxlength="16"></label>
                        <label class="registration-wide">Alamat <textarea name="family_address" rows="3" maxlength="2000" required>{{ old('family_address') }}</textarea></label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Akun untuk memantau pendaftaran</legend>
                    <div class="registration-grid">
                        <label>Email <input type="email" name="email" value="{{ old('email') }}" maxlength="150" autocomplete="email" required></label>
                        <label>Kata sandi
                            <span class="registration-password-wrap">
                                <input id="registration-password" type="password" name="password" minlength="8" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="registration-password" aria-pressed="false">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                                        <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </span>
                            <small>Minimal 8 karakter, huruf besar dan kecil, serta angka. Simbol tidak wajib.</small>
                        </label>
                        <label>Ulangi kata sandi
                            <span class="registration-password-wrap">
                                <input id="registration-password-confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="registration-password-confirmation" aria-pressed="false">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                                        <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Dokumen pendaftaran (5 berkas wajib)</legend>
                    <div class="registration-grid">
                        @foreach ($requiredDocuments as $documentKey => $documentLabel)
                            @php
                                $documentInputId = 'registration-document-'.$documentKey;
                                $documentFeedbackId = $documentInputId.'-feedback';
                            @endphp
                            <label for="{{ $documentInputId }}">{{ $documentLabel }} (PDF/JPG/PNG, maks. 5 MB)
                                <input
                                    id="{{ $documentInputId }}"
                                    type="file"
                                    name="documents[{{ $documentKey }}]"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    data-max-bytes="5242880"
                                    aria-describedby="{{ $documentFeedbackId }}"
                                    required
                                >
                                <small
                                    id="{{ $documentFeedbackId }}"
                                    class="registration-upload-feedback @error('documents.'.$documentKey) is-error @enderror"
                                    role="status"
                                    aria-live="polite"
                                >@error('documents.'.$documentKey){{ $message }}@else Wajib diunggah. Maksimal 5 MB dan 20 megapiksel per gambar.@enderror</small>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <button class="registration-submit" type="submit">Kirim pendaftaran</button>
            </form>
            @endif
        </section>
    </main>
@endsection
