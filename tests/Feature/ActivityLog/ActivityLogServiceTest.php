<?php

namespace Tests\Feature\ActivityLog;

use App\Models\ActivityLog;
use App\Models\Moment;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_activity_with_subject(): void
    {
        $user = User::factory()->create();
        $moment = Moment::factory()->create(['user_id' => $user->id]);
        $service = app(ActivityLogService::class);
        $log = $service->log($user, 'moment_created', $moment, ['test' => true]);
        $this->assertInstanceOf(ActivityLog::class, $log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('moment_created', $log->action);
        $this->assertSame(Moment::class, $log->subject_type);
        $this->assertSame($moment->id, $log->subject_id);
        $this->assertSame(['test' => true], $log->metadata);
    }

    public function test_logs_activity_without_subject(): void
    {
        $user = User::factory()->create();
        $service = app(ActivityLogService::class);
        $log = $service->log($user, 'login');
        $this->assertNull($log->subject_type);
        $this->assertNull($log->subject_id);
    }
}
