<?php

namespace App\Jobs;

use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors one in-app notification to the recipient's phones.
 * Dispatched from AppNotification's "created" hook, so every notification gets one.
 */
final class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly AppNotification $notification)
    {
        $this->onQueue('notifications');
    }

    public function handle(FcmService $fcm): void
    {
        if (! $fcm->isConfigured() || ! $this->notification->user?->push_notifications) {
            return;
        }

        DeviceToken::where('user_id', $this->notification->user_id)->get()
            ->each(function (DeviceToken $device) use ($fcm): void {
                $delivered = $fcm->send(
                    $device->token,
                    $this->notification->title,
                    $this->notification->body,
                    ['notification_id' => (string) $this->notification->id],
                );

                if (! $delivered) {
                    $device->delete();
                }
            });
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendPushNotificationJob failed', [
            'notification' => $this->notification->id,
            'error' => $e->getMessage(),
        ]);
    }
}
