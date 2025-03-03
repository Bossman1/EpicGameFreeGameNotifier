<?php

namespace App\Services;

use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class SmsService
{
    protected Client $client;

    public function __construct()
    {
        $basic = new Basic(config('services.vonage.key'), config('services.vonage.secret'));
        $this->client = new Client($basic);
    }

    public function sendSms(string $to, string $message): string
    {
        try {
            $sms = new SMS($to, config('services.vonage.sms_from'), $message);
            $response = $this->client->sms()->send($sms);
            return $response->current()->getStatus();
        } catch (\Exception $e) {
            \Log::error('Vonage SMS Error: ' . $e->getMessage());
            return 'failed';
        }
    }
}
