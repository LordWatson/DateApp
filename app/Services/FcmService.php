<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends push notifications through Firebase Cloud Messaging (HTTP v1).
 */
class FcmService
{
    public function isConfigured(): bool
    {
        $path = config('services.fcm.credentials');

        return is_string($path) && $path !== '' && is_file($path);
    }

    /**
     * @param  array<string, string>  $data
     * @return bool false when FCM says the token is dead and should be deleted
     */
    public function send(string $deviceToken, string $title, ?string $body, array $data = []): bool
    {
        $credentials = $this->credentials();

        $response = Http::withToken($this->accessToken($credentials))
            ->timeout(10)
            ->post("https://fcm.googleapis.com/v1/projects/{$credentials['project_id']}/messages:send", [
                'message' => array_filter([
                    'token' => $deviceToken,
                    'notification' => array_filter(['title' => $title, 'body' => $body]),
                    'data' => $data,
                ]),
            ]);

        if ($response->notFound() || $response->json('error.details.0.errorCode') === 'UNREGISTERED') {
            return false;
        }

        $response->throw();

        return true;
    }

    /** @return array{project_id: string, client_email: string, private_key: string} */
    private function credentials(): array
    {
        return json_decode((string) file_get_contents(config('services.fcm.credentials')), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Google wants a short-lived OAuth token, bought with a JWT signed by the service account key.
     *
     * @param  array{project_id: string, client_email: string, private_key: string}  $credentials
     */
    private function accessToken(array $credentials): string
    {
        return Cache::remember('fcm_access_token', 3300, function () use ($credentials): string {
            $encode = fn (array $part): string => rtrim(strtr(base64_encode((string) json_encode($part)), '+/', '-_'), '=');
            $now = time();
            $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]);

            if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Could not sign the FCM token request: the service account key is invalid.');
            }

            return Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '='),
            ])->throw()->json('access_token');
        });
    }
}
