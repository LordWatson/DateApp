<?php

namespace Tests\Feature\Settings;

use App\Enums\NotificationType;
use App\Jobs\ExportUserDataJob;
use App\Models\Achievement;
use App\Models\AppNotification;
use App\Models\CalendarEvent;
use App\Models\DateNightPlan;
use App\Models\LoveNote;
use App\Models\Moment;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $partner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(ExportUserDataJob::DISK);
        Mail::fake();

        $this->user = User::factory()->create();
        $this->partner = User::factory()->create(['partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $this->partner->id]);
    }

    public function test_export_holds_the_users_data_and_nothing_that_is_only_the_partners(): void
    {
        $mine = Response::factory()->completed()->create(['user_id' => $this->user->id]);
        $theirs = Response::factory()->completed()->create(['user_id' => $this->partner->id]);
        $theirsAlone = Response::factory()->completed()->create(['user_id' => $this->partner->id]);

        DateNightPlan::factory()->create(['partner_one_response_id' => $mine->id, 'partner_two_response_id' => $theirs->id, 'theme' => 'Shared plan']);
        DateNightPlan::factory()->create(['partner_one_response_id' => $theirsAlone->id, 'partner_two_response_id' => $theirsAlone->id, 'theme' => 'Partner solo plan']);

        Moment::factory()->create(['user_id' => $this->user->id, 'title' => 'My moment', 'private_notes' => 'My private note']);
        Moment::factory()->create(['user_id' => $this->partner->id, 'title' => 'Partner moment', 'private_notes' => 'Partner private note']);

        CalendarEvent::factory()->create(['user_id' => $this->user->id, 'title' => 'My event']);
        CalendarEvent::factory()->create(['user_id' => $this->partner->id, 'title' => 'Partner event']);

        LoveNote::factory()->create(['sender_id' => $this->user->id, 'recipient_id' => $this->partner->id, 'message' => 'Note I sent']);
        LoveNote::factory()->create(['sender_id' => $this->partner->id, 'recipient_id' => $this->user->id, 'message' => 'Note I received']);

        $this->user->achievements()->attach(Achievement::factory()->create(['name' => 'My achievement']), ['unlocked_at' => now()]);
        $this->partner->achievements()->attach(Achievement::factory()->create(['name' => 'Partner achievement']), ['unlocked_at' => now()]);

        AppNotification::create(['user_id' => $this->user->id, 'type' => NotificationType::PartnerConnected, 'title' => 'My notification']);
        AppNotification::create(['user_id' => $this->partner->id, 'type' => NotificationType::PartnerConnected, 'title' => 'Partner notification']);

        $this->actingAs($this->user)->post(route('data-export.store'))->assertRedirect(route('profile.edit'));

        $json = Storage::disk(ExportUserDataJob::DISK)->get(ExportUserDataJob::pathFor($this->user));
        $export = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame($this->user->email, $export['profile']['email']);
        $this->assertSame([$mine->id], array_column($export['questionnaire_answers'], 'id'));
        $this->assertSame(['Shared plan'], array_column($export['date_night_plans'], 'theme'));
        $this->assertSame(['My private note'], array_column($export['moments'], 'private_notes'));
        $this->assertSame(['My event'], array_column($export['calendar_events'], 'title'));
        $this->assertSame(['Note I sent'], array_column($export['love_notes_sent'], 'message'));
        $this->assertSame(['Note I received'], array_column($export['love_notes_received'], 'message'));
        $this->assertSame(['My achievement'], array_column($export['achievements'], 'name'));
        $this->assertSame(['My notification'], array_column($export['notifications'], 'title'));

        foreach (['Partner solo plan', 'Partner moment', 'Partner private note', 'Partner event', 'Partner achievement', 'Partner notification', $this->partner->email] as $partnerOnly) {
            $this->assertStringNotContainsString($partnerOnly, $json);
        }

        $this->assertSame(1, AppNotification::where('user_id', $this->user->id)->where('type', NotificationType::DataExportReady)->count());
    }

    public function test_only_the_owner_can_download_an_export(): void
    {
        $this->get(route('data-export.download'))->assertRedirect(route('login'));

        $this->actingAs($this->user)->get(route('data-export.download'))->assertNotFound();

        $this->actingAs($this->user)->post(route('data-export.store'));

        $download = $this->actingAs($this->user)->get(route('data-export.download'));
        $download->assertOk()->assertDownload('date-night-export.json');
        $this->assertStringContainsString($this->user->email, $download->streamedContent());

        // The partner has no file of their own, and there is no way to name someone else's.
        $this->actingAs($this->partner)->get(route('data-export.download'))->assertNotFound();
    }

    public function test_exports_are_limited_to_one_an_hour_per_user(): void
    {
        Bus::fake([ExportUserDataJob::class]);

        $this->actingAs($this->user)->post(route('data-export.store'));
        $this->actingAs($this->user)->post(route('data-export.store'))->assertRedirect(route('profile.edit'));
        $this->actingAs($this->partner)->post(route('data-export.store'));

        Bus::assertDispatchedTimes(ExportUserDataJob::class, 2);

        $this->travel(61)->minutes();
        $this->actingAs($this->user)->post(route('data-export.store'));

        Bus::assertDispatchedTimes(ExportUserDataJob::class, 3);
    }

    public function test_expired_exports_cannot_be_downloaded_and_are_cleaned_up(): void
    {
        $this->actingAs($this->user)->post(route('data-export.store'));
        $path = ExportUserDataJob::pathFor($this->user);

        $this->artisan('datenight:clean-exports')->assertSuccessful();
        Storage::disk(ExportUserDataJob::DISK)->assertExists($path);

        $this->travel(ExportUserDataJob::RETENTION_HOURS + 1)->hours();

        $this->actingAs($this->user)->get(route('data-export.download'))->assertNotFound();

        $this->artisan('datenight:clean-exports')->assertSuccessful();
        Storage::disk(ExportUserDataJob::DISK)->assertMissing($path);
    }
}
