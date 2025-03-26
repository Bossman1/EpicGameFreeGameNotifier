<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected mixed $apiToken;
    protected mixed $url;
    protected mixed $sender;

    public function __construct()
    {
        // Set the API token and URL for GatewayAPI from config
        $this->apiToken = config('services.smsApi.gatewayapi.token');
        $this->url = config('services.smsApi.gatewayapi.url');
        $this->sender = config('services.smsApi.gatewayapi.sender') ?? 'Null';
    }

    public function sendSms(array $recipients, string $message)
    {
        $json = [
            'sender' => $this->sender,
            'message' => $message,
            'recipients' => [],
        ];

        // Add recipients to the request
        foreach ($recipients as $msisdn) {
            $json['recipients'][] = ['msisdn' => $msisdn];
        }

        // Get current date and time for logging
        $dateTime = now()->toDateTimeString();

        // Log the details of the SMS sending attempt using the 'sms_gateway' channel
        Log::channel('sms_gateway')->info("[$dateTime] Sending SMS", [
            'sender' => $this->sender,
            'message' => $message,
            'recipients' => $recipients
        ]);

        // Make the request using Laravel's HTTP client
        $response = Http::withBasicAuth($this->apiToken, '')
            ->acceptJson()
            ->post($this->url, $json);

        // Check if the response was successful
        if ($response->successful()) {
            $data = $response->json();
            $smsIds = $data['ids'] ?? null;

            // Log success with SMS IDs using the 'sms_gateway' channel
            Log::channel('sms_gateway')->info("[$dateTime] SMS sent successfully", [
                'sms_ids' => $smsIds,
                'recipients' => $recipients
            ]);

            return $smsIds;
        } else {
            // Log failure if the request was not successful using the 'sms_gateway' channel
            Log::channel('sms_gateway')->error("[$dateTime] Failed to send SMS", [
                'error' => $response->body(),
                'recipients' => $recipients
            ]);

            return null;
        }
    }
}
