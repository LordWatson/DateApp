<?php

namespace Tests\Feature;

use App\Models\LoveNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoveNoteTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = User::factory()->create(['onboarding_completed' => true]);
        $this->recipient = User::factory()->create(['onboarding_completed' => true]);

        $this->sender->update(['partner_id' => $this->recipient->id]);
        $this->recipient->update(['partner_id' => $this->sender->id]);
    }

    public function test_user_can_view_love_notes_page(): void
    {
        $this->actingAs($this->sender)
            ->get(route('love-notes.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('love-notes/Index')
                ->has('sent')
                ->has('received')
                ->has('default_messages')
            );
    }

    public function test_user_can_send_a_love_note(): void
    {
        $this->actingAs($this->sender)
            ->post(route('love-notes.store'), ['message' => '❤️ Thinking of you'])
            ->assertRedirect();

        $this->assertDatabaseHas('love_notes', [
            'sender_id' => $this->sender->id,
            'recipient_id' => $this->recipient->id,
            'message' => '❤️ Thinking of you',
        ]);
    }

    public function test_sending_love_note_creates_notification(): void
    {
        $this->actingAs($this->sender)
            ->post(route('love-notes.store'), ['message' => '🥰 Missing you']);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->recipient->id,
            'type' => 'love_note_received',
        ]);
    }

    public function test_love_note_message_is_required(): void
    {
        $this->actingAs($this->sender)
            ->post(route('love-notes.store'), ['message' => ''])
            ->assertSessionHasErrors('message');
    }

    public function test_love_note_message_cannot_exceed_200_characters(): void
    {
        $this->actingAs($this->sender)
            ->post(route('love-notes.store'), ['message' => str_repeat('a', 201)])
            ->assertSessionHasErrors('message');
    }

    public function test_received_notes_are_marked_as_read_on_view(): void
    {
        $note = LoveNote::factory()->create([
            'sender_id' => $this->recipient->id,
            'recipient_id' => $this->sender->id,
            'read_at' => null,
        ]);

        $this->actingAs($this->sender)
            ->get(route('love-notes.index'));

        $this->assertNotNull($note->fresh()->read_at);
    }

    public function test_user_without_partner_cannot_send_note(): void
    {
        $loner = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($loner)
            ->post(route('love-notes.store'), ['message' => '❤️ Hello'])
            ->assertSessionHasErrors('partner');
    }

    public function test_sent_notes_appear_in_sent_tab(): void
    {
        LoveNote::factory()->create([
            'sender_id' => $this->sender->id,
            'recipient_id' => $this->recipient->id,
        ]);

        $this->actingAs($this->sender)
            ->get(route('love-notes.index'))
            ->assertInertia(fn ($page) => $page->has('sent', 1));
    }

    public function test_received_notes_appear_in_received_tab(): void
    {
        LoveNote::factory()->create([
            'sender_id' => $this->recipient->id,
            'recipient_id' => $this->sender->id,
        ]);

        $this->actingAs($this->sender)
            ->get(route('love-notes.index'))
            ->assertInertia(fn ($page) => $page->has('received', 1));
    }
}
