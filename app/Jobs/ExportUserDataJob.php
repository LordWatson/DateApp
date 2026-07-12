<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly User $user,
        public readonly string $format,
    ) {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $data = [
            'user' => $this->user->only(['name', 'email', 'created_at']),
            'responses' => $this->user->responses()->with('answers')->get()->toArray(),
            'moments' => $this->user->moments()->get()->toArray(),
        ];

        $filename = "exports/user-{$this->user->id}-".now()->format('Y-m-d').".{$this->format}";
        Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));

        Log::info('ExportUserDataJob completed', [
            'user' => $this->user->id,
            'file' => $filename,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ExportUserDataJob failed', [
            'user' => $this->user->id,
            'error' => $e->getMessage(),
        ]);
    }
}
