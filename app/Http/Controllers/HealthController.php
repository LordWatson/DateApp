<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];

        $healthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'message' => 'Database unavailable'];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('health_check', true, 10);
            Cache::get('health_check');

            return ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'message' => 'Cache unavailable'];
        }
    }

    private function checkQueue(): array
    {
        try {
            $size = Queue::size();

            return ['status' => 'ok', 'message' => "Queue size: {$size}"];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'message' => 'Queue unavailable'];
        }
    }

    private function checkStorage(): array
    {
        try {
            Storage::put('health_check.txt', 'ok');
            Storage::delete('health_check.txt');

            return ['status' => 'ok', 'message' => 'Writable'];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'message' => 'Storage unavailable'];
        }
    }
}
