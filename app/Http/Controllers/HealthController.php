<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('app.health_token');

        // With a token configured, only the monitor that knows it may run the checks.
        abort_if($token !== '' && ! hash_equals($token, (string) $request->bearerToken()), 404);

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

    /** @return array{status: string, message: string} */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'message' => 'Database unavailable'];
        }
    }

    /** @return array{status: string, message: string} */
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

    /** @return array{status: string, message: string} */
    private function checkQueue(): array
    {
        try {
            $size = Queue::size();

            return ['status' => 'ok', 'message' => "Queue size: {$size}"];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'message' => 'Queue unavailable'];
        }
    }

    /** @return array{status: string, message: string} */
    private function checkStorage(): array
    {
        try {
            $uploads = Storage::disk(config('filesystems.uploads'));
            $uploads->put('health_check.txt', 'ok');
            $uploads->delete('health_check.txt');

            return ['status' => 'ok', 'message' => 'Writable'];
        } catch (\Throwable $e) {
            return ['status' => 'fail', 'message' => 'Storage unavailable'];
        }
    }
}
