<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_sent_message_appears_as_unread_notification_and_is_marked_read_when_opened(): void
    {
        $sender = User::factory()->create(['name' => 'Guru Pengirim', 'role' => 'teacher']);
        $recipient = User::factory()->create(['name' => 'Orang Tua Penerima', 'role' => 'parent']);

        $this->actingAs($sender)
            ->post(route('messages.store'), [
                'recipient_id' => $recipient->getKey(),
                'body' => 'Informasi kegiatan kelas besok.',
            ])
            ->assertRedirect(route('messages.index', ['with' => $recipient->getKey()]));

        $message = Message::query()->sole();
        $this->assertNull($message->read_at);

        $this->actingAs($recipient)
            ->getJson(route('messages.notifications'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('messages.0.sender', 'Guru Pengirim');

        $this->actingAs($recipient)
            ->get(route('messages.index', ['with' => $sender->getKey()]))
            ->assertOk()
            ->assertSee('Informasi kegiatan kelas besok.')
            ->assertSee('Guru Pengirim');

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_users_cannot_read_conversations_between_other_users(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $otherUser = User::factory()->create();

        Message::query()->create([
            'sender_id' => $sender->getKey(),
            'recipient_id' => $recipient->getKey(),
            'body' => 'Percakapan pribadi.',
        ]);

        $this->actingAs($otherUser)
            ->get(route('messages.index', ['with' => $recipient->getKey()]))
            ->assertOk()
            ->assertDontSee('Percakapan pribadi.');
    }

    public function test_user_can_mark_all_received_notifications_as_read(): void
    {
        $sender = User::factory()->create();
        $senderTwo = User::factory()->create();
        $recipient = User::factory()->create();
        $otherRecipient = User::factory()->create();

        $firstMessage = Message::query()->create([
            'sender_id' => $sender->getKey(),
            'recipient_id' => $recipient->getKey(),
            'body' => 'Pesan pertama.',
        ]);
        $secondMessage = Message::query()->create([
            'sender_id' => $senderTwo->getKey(),
            'recipient_id' => $recipient->getKey(),
            'body' => 'Pesan kedua.',
        ]);
        $otherRecipientMessage = Message::query()->create([
            'sender_id' => $sender->getKey(),
            'recipient_id' => $otherRecipient->getKey(),
            'body' => 'Pesan untuk pengguna lain.',
        ]);

        $this->actingAs($recipient)
            ->from(route('dashboard'))
            ->patch(route('messages.notifications.read-all'))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($firstMessage->fresh()->read_at);
        $this->assertNotNull($secondMessage->fresh()->read_at);
        $this->assertNull($otherRecipientMessage->fresh()->read_at);

        $this->actingAs($recipient)
            ->getJson(route('messages.notifications'))
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonPath('messages', []);
    }

    public function test_user_cannot_send_message_to_themselves(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('messages.index'))
            ->post(route('messages.store'), [
                'recipient_id' => $user->getKey(),
                'body' => 'Pesan untuk diri sendiri.',
            ])
            ->assertRedirect(route('messages.index'))
            ->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseCount('messages', 0);
    }
}
