<?php

namespace Tests\Feature\Settings;

use App\Enums\NotificationCategory;
use App\Mail\LoveNoteReceivedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailUnsubscribeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_opening_the_link_asks_first_and_changes_nothing(): void
    {
        $this->get($this->link('love_notes'))
            ->assertOk()
            ->assertSee('Stop these emails?');

        $this->assertTrue($this->user->fresh()->wantsEmail(NotificationCategory::LoveNotes));
    }

    public function test_confirming_turns_off_that_kind_of_email_only(): void
    {
        $this->post($this->link('love_notes'))
            ->assertOk()
            ->assertSee("You're unsubscribed", false);

        $user = $this->user->fresh();
        $this->assertFalse($user->wantsEmail(NotificationCategory::LoveNotes));
        $this->assertTrue($user->wantsEmail(NotificationCategory::Moments));
        $this->assertTrue($user->email_notifications);
    }

    public function test_a_link_without_a_valid_signature_is_refused(): void
    {
        $unsigned = route('email-unsubscribe.show', ['user' => $this->user->id, 'category' => 'love_notes']);
        $someoneElses = str_replace("/{$this->user->id}/", '/'.User::factory()->create()->id.'/', $this->link('love_notes'));

        $this->post($unsigned)->assertForbidden();
        $this->post($someoneElses)->assertForbidden();

        $this->assertTrue($this->user->fresh()->wantsEmail(NotificationCategory::LoveNotes));
    }

    public function test_an_unknown_category_is_not_found(): void
    {
        $this->get($this->link('newsletters'))->assertNotFound();
    }

    public function test_a_notification_email_carries_the_link_for_its_own_category(): void
    {
        $html = (new LoveNoteReceivedMail($this->user, User::factory()->create()))->render();

        $this->assertStringContainsString("email-preferences/{$this->user->id}/love_notes?", $html);
        $this->assertStringContainsString('signature=', $html);
    }

    private function link(string $category): string
    {
        return URL::signedRoute('email-unsubscribe.show', ['user' => $this->user->id, 'category' => $category]);
    }
}
