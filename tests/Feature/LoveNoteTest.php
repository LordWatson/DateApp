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

    public function test_received_notes_are_marked_read_when_the_received_tab_is_opened(): void
    {
        $note = LoveNote::factory()->create([
            'sender_id' => $this->recipient->id,
            'recipient_id' => $this->sender->id,
            'read_at' => null,
        ]);
        $someoneElses = LoveNote::factory()->create(['read_at' => null]);

        // Loading the page opens the Send tab, so nothing has been read yet.
        $this->actingAs($this->sender)->get(route('love-notes.index'));
        $this->assertNull($note->fresh()->read_at);

        $this->actingAs($this->sender)->postJson(route('love-notes.mark-read'))->assertOk();

        $this->assertNotNull($note->fresh()->read_at);
        $this->assertNull($someoneElses->fresh()->read_at);
    }

    public function test_the_recipient_can_react_and_the_sender_is_told_once(): void
    {
        $note = $this->noteToRecipient();

        $this->actingAs($this->recipient)
            ->post(route('love-notes.react', $note), ['reaction' => '❤️'])
            ->assertRedirect();

        $this->assertSame('❤️', $note->fresh()->reaction);
        $this->assertNotNull($note->fresh()->reacted_at);

        // Changing it is not announced again.
        $this->actingAs($this->recipient)->post(route('love-notes.react', $note), ['reaction' => '🔥']);

        $this->assertSame('🔥', $note->fresh()->reaction);
        $this->assertDatabaseCount('app_notifications', 1);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->sender->id,
            'type' => 'love_note_reaction',
        ]);

        $this->actingAs($this->sender)
            ->get(route('love-notes.index'))
            ->assertInertia(fn ($page) => $page->where('sent.0.reaction', '🔥'));
    }

    public function test_a_reaction_can_be_cleared(): void
    {
        $note = $this->noteToRecipient(['reaction' => '❤️', 'reacted_at' => now()]);

        $this->actingAs($this->recipient)
            ->post(route('love-notes.react', $note), ['reaction' => null])
            ->assertRedirect();

        $this->assertNull($note->fresh()->reaction);
        $this->assertNull($note->fresh()->reacted_at);
    }

    public function test_only_the_recipient_can_react(): void
    {
        $note = $this->noteToRecipient();
        $stranger = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($this->sender)->post(route('love-notes.react', $note), ['reaction' => '❤️'])->assertForbidden();
        $this->actingAs($stranger)->post(route('love-notes.react', $note), ['reaction' => '❤️'])->assertForbidden();

        $this->assertNull($note->fresh()->reaction);
    }

    public function test_a_reaction_must_be_one_of_the_offered_emoji(): void
    {
        $note = $this->noteToRecipient();

        $this->actingAs($this->recipient)
            ->post(route('love-notes.react', $note), ['reaction' => '💩'])
            ->assertSessionHasErrors('reaction');

        $this->assertNull($note->fresh()->reaction);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function noteToRecipient(array $attributes = []): LoveNote
    {
        return LoveNote::factory()->create([
            'sender_id' => $this->sender->id,
            'recipient_id' => $this->recipient->id,
            ...$attributes,
        ]);
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
