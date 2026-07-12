<?php

namespace Tests\Feature\Commands;

use App\Jobs\DailyChallengeSelectionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SelectDailyChallengeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_daily_challenge_selection_job(): void
    {
        Queue::fake();
        $this->artisan('datenight:select-daily-challenge')->assertSuccessful();
        Queue::assertPushed(DailyChallengeSelectionJob::class);
    }
}
