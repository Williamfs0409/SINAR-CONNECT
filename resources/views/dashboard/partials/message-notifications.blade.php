@if ($messageCenterReady)
    <div class="message-notifications"
         data-message-notifications
         data-notifications-url="{{ route('messages.notifications') }}">
        <button class="message-notifications-toggle"
                type="button"
                aria-label="Notifikasi pesan"
                aria-expanded="false"
                aria-controls="message-notification-panel"
                data-message-notifications-toggle>
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
            </svg>
            <span class="message-unread-badge"
                  data-message-unread-badge
                  aria-live="polite"
                  @if ($unreadMessageCount === 0) hidden @endif>{{ $unreadMessageCount > 99 ? '99+' : $unreadMessageCount }}</span>
        </button>
        <section class="message-notification-panel"
                 id="message-notification-panel"
                 data-message-notification-panel
                 aria-label="Notifikasi pesan"
                 hidden>
            <div class="message-notification-heading">
                <strong>Pesan masuk</strong>
                <a href="{{ route('messages.index') }}">Semua pesan</a>
            </div>
            @if ($unreadMessageCount > 0)
                <form class="message-mark-all-read" method="POST" action="{{ route('messages.notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit">Tandai semua dibaca</button>
                </form>
            @endif
            <div data-message-notification-items>
                @forelse ($recentUnreadMessages as $message)
                    <a class="message-notification-item"
                       href="{{ route('messages.index', ['with' => $message->sender_id]) }}">
                        <strong>{{ $message->sender->name }}</strong>
                        <span>{{ \Illuminate\Support\Str::limit($message->body, 90) }}</span>
                    </a>
                @empty
                    <p class="message-notification-empty">Belum ada pesan baru.</p>
                @endforelse
            </div>
        </section>
    </div>
@else
    <a class="message-notifications-toggle"
       href="{{ route('messages.index') }}"
       aria-label="Fitur pesan belum aktif; perlu menjalankan migrasi database"
       title="Fitur pesan belum aktif; perlu menjalankan migrasi database">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
        </svg>
    </a>
@endif
