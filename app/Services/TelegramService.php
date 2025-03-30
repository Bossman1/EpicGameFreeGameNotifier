<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
    protected $token;
    protected $chatId;
    protected $chatUrl;
    protected $weburl;


    public function __construct()
    {
        $this->token = config('services.telegram.token');
        $this->chatId = config('services.telegram.chat_id');
        $this->chatUrl = "https://api.telegram.org/bot{$this->token}/";
        $this->weburl = config('services.telegram.web_url');
    }

    public function sendTelegramMessage($message, $photo = null, $parse_mode = 'Markdown')
    {

        $endpint = !is_null($photo) ? 'sendPhoto' : 'sendMessage';
        $payload = [
                "chat_id" => $this->chatId,
                'parse_mode' => $parse_mode,
            ] + ($photo ? ['photo' => $photo, 'caption' => $message] : ['text' => $message]);


        try {
            $response = Http::post($this->chatUrl . $endpint, $payload);
            if ($response->successful()) {
                return $response;
                \Log::info('Request successful');
            } else {
                \Log::error('Telegram message Request failed with status: ' . $response->status());
            }
        } catch (\Exception $e) {
            \Log::error("Telegram message Error occurred while making HTTP request: " . $e->getMessage());
        }


        return false;
    }
}
