@extends('layouts.app')

@section('title', 'Pesan — SINAR-CONNECT')
@section('body-class', 'message-center-page')

@section('content')
    @php
        $roleLabels = [
            'admin' => 'Administrator',
            'principal' => 'Kepala Sekolah',
            'teacher' => 'Guru',
            'parent' => 'Orang Tua',
        ];
    @endphp
    <div class="message-center-shell">
        <header class="message-center-header">
            <a class="database-brand" href="{{ route('dashboard') }}">
                <span class="database-brand-mark">S</span>
                <span><strong>SINAR-CONNECT</strong><small>Portal sekolah</small></span>
            </a>
            <div class="message-center-account">
                <div class="database-topbar-profile">
                    @if ($user->profile_photo_path)
                        <img class="database-topbar-avatar" src="{{ route('profile.photo', ['user' => $user->getKey()]) }}" alt="">
                    @else
                        <span class="database-topbar-avatar">{{ mb_substr($user->name, 0, 1) }}</span>
                    @endif
                    <span><strong>{{ $user->name }}</strong><small>{{ $roleLabels[$user->role] ?? $user->role }}</small></span>
                </div>
                <a class="message-dashboard-link" href="{{ route('dashboard') }}">Kembali ke dashboard</a>
            </div>
        </header>

        <main class="message-center-main">
            <div class="message-center-heading">
                <span class="database-eyebrow">KOMUNIKASI SEKOLAH</span>
                <h1>Pesan</h1>
                <p>Kirim pesan pribadi kepada pengguna aktif SINAR-CONNECT.</p>
            </div>

            @if (session('status'))
                <div class="database-flash" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="database-form-errors" role="alert">
                    <strong>Ada data yang perlu diperbaiki:</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="message-center-card" aria-label="Pusat pesan">
                <aside class="message-contact-panel">
                    <div class="message-panel-heading">
                        <strong>Pengguna</strong>
                        <span>{{ $contacts->count() }}</span>
                    </div>
                    <div class="message-contact-list">
                        @forelse ($contacts as $contact)
                            @php($contactUnreadCount = (int) ($unreadCounts[$contact->id_user] ?? 0))
                            <a class="message-contact-link {{ $selectedContact?->is($contact) ? 'is-selected' : '' }}"
                               href="{{ route('messages.index', ['with' => $contact->id_user]) }}">
                                @if ($contact->profile_photo_path)
                                    <img class="message-contact-avatar" src="{{ route('profile.photo', ['user' => $contact->getKey()]) }}" alt="">
                                @else
                                    <span class="message-contact-avatar">{{ mb_substr($contact->name, 0, 1) }}</span>
                                @endif
                                <span class="message-contact-copy">
                                    <strong>{{ $contact->name }}</strong>
                                    <small>{{ $roleLabels[$contact->role] ?? $contact->role }}</small>
                                </span>
                                @if ($contactUnreadCount > 0)
                                    <span class="message-contact-unread">{{ $contactUnreadCount > 99 ? '99+' : $contactUnreadCount }}</span>
                                @endif
                            </a>
                        @empty
                            <p class="message-empty-contact">Belum ada pengguna lain yang aktif.</p>
                        @endforelse
                    </div>
                </aside>

                <div class="message-conversation-panel">
                    @if ($selectedContact)
                        <header class="message-conversation-header">
                            @if ($selectedContact->profile_photo_path)
                                <img class="message-contact-avatar" src="{{ route('profile.photo', ['user' => $selectedContact->getKey()]) }}" alt="">
                            @else
                                <span class="message-contact-avatar">{{ mb_substr($selectedContact->name, 0, 1) }}</span>
                            @endif
                            <span>
                                <strong>{{ $selectedContact->name }}</strong>
                                <small>{{ $roleLabels[$selectedContact->role] ?? $selectedContact->role }}</small>
                            </span>
                        </header>

                        <div class="message-thread" aria-live="polite">
                            @forelse ($thread as $message)
                                @php($isOwnMessage = (int) $message->sender_id === (int) $user->getKey())
                                <article class="message-bubble {{ $isOwnMessage ? 'is-own' : 'is-incoming' }}">
                                    <p>{{ $message->body }}</p>
                                    <time datetime="{{ $message->created_at->toIso8601String() }}">
                                        {{ $message->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                                    </time>
                                </article>
                            @empty
                                <p class="message-thread-empty">Belum ada percakapan. Kirim pesan pertama.</p>
                            @endforelse
                        </div>

                        <form class="message-compose-form" method="POST" action="{{ route('messages.store') }}">
                            @csrf
                            <input type="hidden" name="recipient_id" value="{{ $selectedContact->getKey() }}">
                            <label class="sr-only" for="message-body">Isi pesan</label>
                            <textarea id="message-body" name="body" rows="3" maxlength="2000" required placeholder="Tulis pesan...">{{ old('body') }}</textarea>
                            <div class="message-compose-footer">
                                <small>Maksimal 2.000 karakter</small>
                                <button class="database-button database-button-primary" type="submit">Kirim pesan</button>
                            </div>
                        </form>
                    @else
                        <div class="message-no-selection">
                            <span aria-hidden="true">✉</span>
                            <strong>Pilih pengguna untuk memulai</strong>
                            <p>Pilih nama di sebelah kiri untuk membuka percakapan atau mengirim pesan baru.</p>
                        </div>
                    @endif
                </div>
            </section>
        </main>
    </div>
@endsection
