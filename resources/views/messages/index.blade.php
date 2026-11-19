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
                <img class="database-brand-mark" src="{{ asset('images/sinar-school-logo.png') }}" alt="">
                <span><strong>SINAR-CONNECT</strong><small>TK Najmah Rugayah Darus</small></span>
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
                    <div class="message-contact-search">
                        <input type="search" data-contact-search placeholder="Cari nama atau peran..." aria-label="Cari pengguna">
                        <div class="message-role-filters" role="group" aria-label="Filter peran">
                            <button type="button" class="message-role-chip is-active" data-contact-role="all">Semua</button>
                            <button type="button" class="message-role-chip" data-contact-role="teacher">Guru</button>
                            <button type="button" class="message-role-chip" data-contact-role="parent">Orang Tua</button>
                            <button type="button" class="message-role-chip" data-contact-role="staff">Admin/Kepsek</button>
                        </div>
                    </div>
                    <div class="message-contact-list">
                        @forelse ($contacts as $contact)
                            @php
                                $contactUnreadCount = (int) ($unreadCounts[$contact->id_user] ?? 0);
                                $latestMessage = ($latestByContact ?? collect())->get((int) $contact->id_user);
                                $filterRole = in_array($contact->role, ['admin', 'principal'], true) ? 'staff' : $contact->role;
                            @endphp
                            <a class="message-contact-link {{ $selectedContact?->is($contact) ? 'is-selected' : '' }}"
                               data-contact-item
                               data-role-group="{{ $filterRole }}"
                               href="{{ route('messages.index', ['with' => $contact->id_user]) }}">
                                @if ($contact->profile_photo_path)
                                    <img class="message-contact-avatar" src="{{ route('profile.photo', ['user' => $contact->getKey()]) }}" alt="">
                                @else
                                    <span class="message-contact-avatar">{{ mb_substr($contact->name, 0, 1) }}</span>
                                @endif
                                <span class="message-contact-copy">
                                    <span class="message-contact-top">
                                        <strong>{{ $contact->name }}</strong>
                                        @if ($latestMessage)
                                            <time>{{ $latestMessage->created_at->timezone('Asia/Jakarta')->isToday() ? $latestMessage->created_at->timezone('Asia/Jakarta')->format('H:i') : $latestMessage->created_at->timezone('Asia/Jakarta')->format('d/m') }}</time>
                                        @endif
                                    </span>
                                    <small class="message-contact-preview">
                                        @if ($latestMessage)
                                            {{ (int) $latestMessage->sender_id === (int) $user->getKey() ? 'Anda: ' : '' }}{{ \Illuminate\Support\Str::limit($latestMessage->body, 36) }}
                                        @else
                                            {{ $roleLabels[$contact->role] ?? $contact->role }}
                                        @endif
                                    </small>
                                </span>
                                @if ($contactUnreadCount > 0)
                                    <span class="message-contact-unread">{{ $contactUnreadCount > 99 ? '99+' : $contactUnreadCount }}</span>
                                @endif
                            </a>
                        @empty
                            <p class="message-empty-contact">Belum ada pengguna lain yang aktif.</p>
                        @endforelse
                        <p class="message-empty-contact" data-contact-search-empty hidden>Pengguna tidak ditemukan.</p>
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

                        <div class="message-thread"
                             aria-live="polite"
                             data-message-thread
                             data-thread-url="{{ route('messages.thread', ['contact' => $selectedContact->getKey()]) }}"
                             data-csrf-token="{{ csrf_token() }}">
                            @php
                                $lastDate = null;
                            @endphp
                            @forelse ($thread as $message)
                                @php
                                    $isOwnMessage = (int) $message->sender_id === (int) $user->getKey();
                                    $msgDate = $message->created_at->timezone('Asia/Jakarta');
                                    $dateKey = $msgDate->toDateString();
                                    $dateLabel = $msgDate->isToday()
                                        ? 'Hari ini'
                                        : ($msgDate->isYesterday() ? 'Kemarin' : $msgDate->locale('id')->translatedFormat('d M Y'));
                                @endphp
                                @if ($lastDate !== $dateKey)
                                    <div class="message-date-divider"><span>{{ $dateLabel }}</span></div>
                                    @php
                                        $lastDate = $dateKey;
                                    @endphp
                                @endif
                                <article class="message-bubble {{ $isOwnMessage ? 'is-own' : 'is-incoming' }}">
                                    <p>{{ $message->body }}</p>
                                    <div class="message-bubble-meta">
                                        <time datetime="{{ $message->created_at->toIso8601String() }}">
                                            {{ $msgDate->format('H:i') }}
                                        </time>
                                        @if ($isOwnMessage)
                                            <span class="message-read-tick {{ $message->read_at ? 'is-read' : '' }}" title="{{ $message->read_at ? 'Sudah dibaca' : 'Terkirim' }}">
                                                {{ $message->read_at ? '✓✓' : '✓' }}
                                            </span>
                                            <form method="POST" action="{{ route('messages.destroy', ['message' => $message->getKey()]) }}" class="message-delete-form" data-confirm="Hapus pesan ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="message-delete-button" title="Hapus pesan" aria-label="Hapus pesan">×</button>
                                            </form>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <p class="message-thread-empty">Belum ada percakapan. Kirim pesan pertama.</p>
                            @endforelse
                        </div>

                        <form class="message-compose-form" method="POST" action="{{ route('messages.store') }}" data-message-compose>
                            @csrf
                            <input type="hidden" name="recipient_id" value="{{ $selectedContact->getKey() }}">
                            <label class="sr-only" for="message-body">Isi pesan</label>
                            <textarea id="message-body" name="body" rows="2" maxlength="2000" required placeholder="Ketik pesan... (Enter untuk kirim, Shift+Enter baris baru)">{{ old('body') }}</textarea>
                            <div class="message-compose-footer">
                                <small>Maksimal 2.000 karakter · Enter untuk kirim</small>
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
