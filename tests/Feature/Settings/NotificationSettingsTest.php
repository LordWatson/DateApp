<?php

namespace Tests\Feature\Settings;

use App\Enums\NotificationType;
use App\Events\QuestionnaireCompleted;
use App\Jobs\SendLoveNoteNotificationJob;
use App\Jobs\SendMomentNotificationJob;
use App\Listeners\NotifyPartnerOnQuestionnaireCompleted;
use App\Mail\LoveNoteReceivedMail;
use App\Mail\MomentCreatedMail;
use App\Mail\QuestionnaireCompletedMail;
use App\Models\AppNotification;
use App\Models\LoveNote;
use App\Models\Moment;
use App\Models\Questionnaire;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_settings_page_shows_the_current_choice(): void
    {
        $user = User::factory()->create(['email_notifications' => false]);

        $this->actingAs($user)
            ->get(route('notification-settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->component('settings/Notifications')
                ->where('emailNotifications', false));
    }

    public function test_email_notifications_can_be_turned_off_and_on(): void
    {
        $user = User::factory()->create(['email_notifications' => true]);

        $this->actingAs($user)
            ->patch(route('notification-settings.update'), ['email_notifications' => false])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('notification-settings.edit'));

        $this->assertFalse($user->refresh()->email_notifications);

        $this->actingAs($user)->patch(route('notification-settings.update'), ['email_notifications' => true]);

        $this->assertTrue($user->refresh()->email_notifications);
    }

    public function test_a_value_that_is_not_a_yes_or_no_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('notification-settings.update'), ['email_notifications' => 'sometimes'])
            ->assertSessionHasErrors('email_notifications');

        $this->actingAs($user)
            ->patch(route('notification-settings.update'), [])
            ->assertSessionHasErrors('email_notifications');
    }

    /**
     * @return array<string, array{0: bool}>
     */
    public static function emailChoices(): array
    {
        return ['emails on' => [true], 'emails off' => [false]];
    }

    #[DataProvider('emailChoices')]
    public function test_love_note_email_follows_the_setting_and_the_notification_is_always_created(bool $wantsEmail): void
    {
        Mail::fake();

        [$recipient, $sender] = $this->couple($wantsEmail);
        $note = LoveNote::factory()->create(['sender_id' => $sender->id, 'recipient_id' => $recipient->id]);

        SendLoveNoteNotificationJob::dispatch($recipient, $note);

        $this->assertMailQueued(LoveNoteReceivedMail::class, $wantsEmail);
        $this->assertSame(1, AppNotification::where('user_id', $recipient->id)->where('type', NotificationType::LoveNoteReceived)->count());
    }

    #[DataProvider('emailChoices')]
    public function test_moment_email_follows_the_setting_and_the_notification_is_always_created(bool $wantsEmail): void
    {
        Mail::fake();

        [$recipient, $creator] = $this->couple($wantsEmail);
        $moment = Moment::factory()->create(['user_id' => $creator->id]);

        SendMomentNotificationJob::dispatch($recipient, $moment);

        $this->assertMailQueued(MomentCreatedMail::class, $wantsEmail);
        $this->assertSame(1, AppNotification::where('user_id', $recipient->id)->where('type', NotificationType::MomentCreated)->count());
    }

    #[DataProvider('emailChoices')]
    public function test_questionnaire_completed_email_follows_the_setting_and_the_notification_is_always_created(bool $wantsEmail): void
    {
        Mail::fake();

        [$recipient, $sender] = $this->couple($wantsEmail);
        $questionnaire = Questionnaire::factory()->create(['is_solo' => false]);
        $response = Response::factory()->completed()->create(['user_id' => $sender->id, 'questionnaire_id' => $questionnaire->id]);

        app(NotifyPartnerOnQuestionnaireCompleted::class)->handle(new QuestionnaireCompleted($sender, $questionnaire, $response));

        $this->assertMailQueued(QuestionnaireCompletedMail::class, $wantsEmail);
        $this->assertSame(1, AppNotification::where('user_id', $recipient->id)->where('type', NotificationType::PartnerCompleted)->count());
    }

    /**
     * @return array{0: User, 1: User} the person being notified, then their partner
     */
    private function couple(bool $recipientWantsEmail): array
    {
        $recipient = User::factory()->create(['email_notifications' => $recipientWantsEmail]);
        $partner = User::factory()->create(['partner_id' => $recipient->id]);
        $recipient->update(['partner_id' => $partner->id]);

        return [$recipient, $partner];
    }

    /**
     * @param  class-string  $mailable
     */
    private function assertMailQueued(string $mailable, bool $expected): void
    {
        $expected ? Mail::assertQueued($mailable) : Mail::assertNotQueued($mailable);
    }
}
