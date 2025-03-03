<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;

class VonageService
{
    protected $apiUrl;
    protected $apiKey;
    protected $apiSecret;

    public function __construct()
    {
        $this->apiUrl = config('services.vonage.messages_api_url');
        $this->apiKey = config('services.vonage.key'); // Store in .env
        $this->apiSecret = config('services.vonage.secret'); // Store in .env
    }

    public function sendWhatsAppMessage($to, $message, $imageUrl = null)
    {


        $payload = [
            "from" => config('services.vonage.sms_from'),
            "to" => $to,
            "channel" => "whatsapp",
        ];

        if ($imageUrl) {
            $payload["message_type"] = "image";
            $payload["image"] = ["url" => $imageUrl, 'caption' => $message];
        } else {
            $payload["message_type"] = "text";
            $payload["text"] = $message;
        }

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($this->apiUrl, $payload);

        return $response->json();
    }
}
