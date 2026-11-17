@extends('layouts.app')

@section('title', 'Permintaan Reset Kata Sandi — SINAR')
@section('body-class', 'password-reset-admin-page')

@section('content')
    <main class="password-reset-admin-shell">
        <a class="password-reset-admin-back" href="{{ route('dashboard.feature', ['section' => 'pengguna']) }}">← Kembali ke pengguna</a>

        <section class="password-reset-admin-card" aria-labelledby="password-reset-admin-title">
            <header class="password-reset-admin-heading">
                <p class="password-security-eyebrow">Verifikasi Administrator</p>
                <h1 id="password-reset-admin-title">Permintaan reset kata sandi</h1>
                <p>Pastikan identitas pemohon melalui kanal resmi sebelum menyetujui. Selama masa persetujuan, siapa pun yang memasukkan email akun tersebut dapat membuka formulir sandi baru.</p>
            </header>

            @if (session('status'))
                <p class="password-security-status" role="status">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <div class="password-security-errors" role="alert">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="password-reset-request-list">
                @forelse ($requests as $resetRequest)
                    <article class="password-reset-request">
                        <div class="password-reset-request-details">
                            <strong>{{ $resetRequest->user->name }}</strong>
                            <span>{{ $resetRequest->user->email }}</span>
                            <small>Diajukan {{ $resetRequest->created_at->format('d M Y, H:i') }}</small>
                        </div>
                        <div class="password-reset-request-actions">
                            @if ($resetRequest->status === 'pending')
                                <form method="POST" action="{{ route('admin.password-reset-requests.approve', $resetRequest->id) }}">
                                    @csrf
                                    <button class="database-button" type="submit">Verifikasi permintaan</button>
                                </form>
                                <form method="POST" action="{{ route('admin.password-reset-requests.reject', $resetRequest->id) }}">
                                    @csrf
                                    <button class="database-button database-button-danger" type="submit">Tolak</button>
                                </form>
                            @else
                                <span class="password-reset-request-status password-reset-request-status-{{ $resetRequest->status }}">
                                    @if ($resetRequest->status === 'approved')
                                        Menunggu pemilik mengatur sandi
                                    @elseif ($resetRequest->status === 'completed')
                                        Sandi sudah diganti
                                    @elseif ($resetRequest->status === 'expired')
                                        Masa persetujuan habis
                                    @elseif ($resetRequest->status === 'superseded')
                                        Permintaan duplikat
                                    @else
                                        Ditolak
                                    @endif
                                </span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="database-empty-state">
                        <strong>Belum ada permintaan reset</strong>
                        <p>Permintaan baru dari halaman lupa kata sandi akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>

            @if ($requests->hasPages())
                <div class="database-pagination">{{ $requests->links() }}</div>
            @endif
        </section>
    </main>
@endsection
