<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextBeeService
{
    protected string $deviceId;
    protected string $apiKey;
    protected string $baseUrl = 'https://api.textbee.dev/api/v1/gateway/devices';

    public function __construct()
    {
        $this->deviceId = env('TEXTBEE_DEVICE_ID');
        $this->apiKey   = env('TEXTBEE_API_KEY');

        if (!$this->deviceId || !$this->apiKey) {
            Log::error('TextBeeService misconfigured: missing DEVICE_ID or API_KEY');
        }
    }

    public function sendSms($recipients, string $message): ?array
    {
        if (is_string($recipients)) {
            $recipients = [$recipients];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // Force IPv4
                ],
                'timeout' => 10, // Optional: timeout in seconds
            ])->post("{$this->baseUrl}/{$this->deviceId}/send-sms", [
                'recipients' => $recipients,
                'message'    => $message,
            ]);

            if ($response->failed()) {
                Log::error('TextBee SMS failed', [
                    'recipients' => $recipients,
                    'message' => $message,
                    'response' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('TextBee SMS exception', [
                'recipients' => $recipients,
                'message' => $message,
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
