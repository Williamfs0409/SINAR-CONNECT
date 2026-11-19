<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureMessageTableExists();
        /** @var User $user */
        $user = $request->user();
        $selectedContact = null;
        $thread = collect();
        $contactId = $request->integer('with');

        if ($contactId > 0) {
            $selectedContact = User::query()
                ->where('is_active', true)
                ->where('id_user', '<>', $user->getKey())
                ->findOrFail($contactId);

            Message::query()
                ->where('sender_id', $selectedContact->getKey())
                ->where('recipient_id', $user->getKey())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $thread = Message::query()
                ->with(['sender:id_user,name,role,profile_photo_path', 'recipient:id_user,name,role,profile_photo_path'])
                ->where(function ($query) use ($user, $selectedContact): void {
                    $query->where('sender_id', $user->getKey())
                        ->where('recipient_id', $selectedContact->getKey());
                })
                ->orWhere(function ($query) use ($user, $selectedContact): void {
                    $query->where('sender_id', $selectedContact->getKey())
                        ->where('recipient_id', $user->getKey());
                })
                ->latest()
                ->limit(100)
                ->get()
                ->reverse()
                ->values();
        }

        $recentMessages = Message::query()
            ->with(['sender:id_user,name,role,profile_photo_path', 'recipient:id_user,name,role,profile_photo_path'])
            ->where(function ($query) use ($user): void {
                $query->where('sender_id', $user->getKey())
                    ->orWhere('recipient_id', $user->getKey());
            })
            ->latest()
            ->limit(200)
            ->get();

        $unreadCounts = Message::query()
            ->where('recipient_id', $user->getKey())
            ->whereNull('read_at')
            ->selectRaw('sender_id, COUNT(*) as aggregate')
            ->groupBy('sender_id')
            ->pluck('aggregate', 'sender_id');

        $conversations = $recentMessages
            ->groupBy(fn (Message $message): int => (int) (
            (int) $message->sender_id === (int) $user->getKey()
                    ? $message->recipient_id
                    : $message->sender_id
            ))
            ->map(function (Collection $messages, int $otherUserId) use ($user, $unreadCounts): array {
                $latestMessage = $messages->first();
                $contact = (int) $latestMessage->sender_id === (int) $user->getKey()
                    ? $latestMessage->recipient
                    : $latestMessage->sender;

                return [
                    'contact' => $contact,
                    'latestMessage' => $latestMessage,
                    'unreadCount' => (int) ($unreadCounts[$otherUserId] ?? 0),
                ];
            })
            ->values();

        $contacts = User::query()
            ->where('is_active', true)
            ->where('id_user', '<>', $user->getKey())
            ->orderBy('name')
            ->get(['id_user', 'name', 'role', 'profile_photo_path']);

        $latestByContact = $conversations->mapWithKeys(fn (array $item): array => [
            (int) $item['contact']->getKey() => $item['latestMessage'],
        ]);

        $contacts = $contacts->sortByDesc(function (User $contact) use ($latestByContact): int {
            $latest = $latestByContact->get((int) $contact->getKey());

            return $latest ? $latest->created_at->getTimestamp() : 0;
        })->values();

        return view('messages.index', [
            'user' => $user,
            'contacts' => $contacts,
            'selectedContact' => $selectedContact,
            'thread' => $thread,
            'conversations' => $conversations,
            'latestByContact' => $latestByContact,
            'unreadCounts' => $unreadCounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureMessageTableExists();
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'recipient_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id_user')->where('is_active', true),
            ],
            'body' => ['required', 'string', 'max:2000'],
        ], [
            'recipient_id.required' => 'Pilih penerima pesan.',
            'recipient_id.exists' => 'Penerima tidak ditemukan atau akunnya tidak aktif.',
            'body.required' => 'Isi pesan tidak boleh kosong.',
            'body.max' => 'Pesan maksimal 2.000 karakter.',
        ]);

        $recipient = User::query()
            ->where('is_active', true)
            ->where('id_user', '<>', $user->getKey())
            ->find($validated['recipient_id']);

        if (! $recipient) {
            return back()
                ->withErrors(['recipient_id' => 'Pilih penerima aktif selain akun Anda.'])
                ->withInput();
        }

        Message::query()->create([
            'sender_id' => $user->getKey(),
            'recipient_id' => $recipient->getKey(),
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('messages.index', ['with' => $recipient->getKey()])
            ->with('status', 'Pesan berhasil dikirim.');
    }

    public function notifications(Request $request): JsonResponse
    {
        $this->ensureMessageTableExists();
        /** @var User $user */
        $user = $request->user();
        $unread = Message::query()
            ->with('sender:id_user,name')
            ->where('recipient_id', $user->getKey())
            ->whereNull('read_at')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'count' => Message::query()
                ->where('recipient_id', $user->getKey())
                ->whereNull('read_at')
                ->count(),
            'messages' => $unread->map(fn (Message $message): array => [
                'sender' => $message->sender->name,
                'preview' => Str::limit($message->body, 90),
                'url' => route('messages.index', ['with' => $message->sender_id]),
            ])->values(),
        ]);
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $this->ensureMessageTableExists();
        Message::query()
            ->where('recipient_id', $request->user()->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return redirect()->back()->with('status', 'Semua notifikasi pesan ditandai sudah dibaca.');
    }

    public function destroy(Request $request, Message $message): RedirectResponse
    {
        $this->ensureMessageTableExists();
        abort_unless((int) $message->sender_id === (int) $request->user()->getKey(), 403);

        $recipientId = $message->recipient_id;
        $message->delete();

        return redirect()
            ->route('messages.index', ['with' => $recipientId])
            ->with('status', 'Pesan berhasil dihapus.');
    }

    public function thread(Request $request, User $contact): JsonResponse
    {
        $this->ensureMessageTableExists();
        /** @var User $user */
        $user = $request->user();
        abort_unless($contact->is_active && (int) $contact->getKey() !== (int) $user->getKey(), 404);

        Message::query()
            ->where('sender_id', $contact->getKey())
            ->where('recipient_id', $user->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::query()
            ->where(function ($query) use ($user, $contact): void {
                $query->where('sender_id', $user->getKey())
                    ->where('recipient_id', $contact->getKey());
            })
            ->orWhere(function ($query) use ($user, $contact): void {
                $query->where('sender_id', $contact->getKey())
                    ->where('recipient_id', $user->getKey());
            })
            ->latest()
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages->map(fn (Message $m): array => [
                'id' => $m->getKey(),
                'is_own' => (int) $m->sender_id === (int) $user->getKey(),
                'body' => $m->body,
                'time' => $m->created_at->timezone('Asia/Jakarta')->format('H:i'),
                'date' => $m->created_at->timezone('Asia/Jakarta')->toDateString(),
                'read' => $m->read_at !== null,
                'delete_url' => route('messages.destroy', ['message' => $m->getKey()]),
            ]),
        ]);
    }

    private function ensureMessageTableExists(): void
    {
        abort_unless(
            Schema::hasTable('messages'),
            503,
            'Fitur pesan belum aktif. Jalankan migrasi database terlebih dahulu.'
        );
    }
}
