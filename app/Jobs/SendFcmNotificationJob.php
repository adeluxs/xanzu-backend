<?php

namespace App\Jobs;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 20;

    public function __construct(
        public readonly string $token,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {
    }

    public function handle(): void
    {
        $firebase = plugin_active('Firebase');
        if (! $firebase) {
            return;
        }

        $firebaseData = json_decode((string) $firebase->data, true);
        $relativePath = (string) data_get($firebaseData, 'upload_account_json', '');
        if ($relativePath === '') {
            return;
        }

        $jsonPath = base_path('assets/' . ltrim($relativePath, '/\\'));
        $jsonData = @json_decode((string) @file_get_contents($jsonPath), true);
        $projectId = (string) data_get($jsonData, 'project_id', '');
        if (! is_array($jsonData) || $projectId === '') {
            Log::warning('FCM delivery skipped: invalid Firebase service account configuration.');
            return;
        }

        $cacheKey = 'firebase.messaging.oauth.' . sha1($jsonPath . '|' . $projectId);
        $bearerToken = Cache::remember($cacheKey, now()->addMinutes(45), function () use ($jsonData) {
            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $jsonData
            );

            return (string) data_get($credentials->fetchAuthToken(), 'access_token', '');
        });

        if ($bearerToken === '') {
            return;
        }

        $payloadData = [];
        foreach ($this->data as $key => $value) {
            $payloadData[(string) $key] = is_scalar($value) || $value === null
                ? (string) ($value ?? '')
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $response = Http::acceptJson()
            ->withToken($bearerToken)
            ->connectTimeout(3)
            ->timeout(8)
            ->retry(1, 150, throw: false)
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => [
                    'token' => $this->token,
                    'notification' => [
                        'title' => $this->title,
                        'body' => $this->body,
                    ],
                    'data' => $payloadData + [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'id' => '1',
                        'status' => 'done',
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('FCM delivery failed.', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 1000),
            ]);
        }
    }
}
