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

    public function sendTelegramMessage($message, $photo = null)
    {
        $endpint = !is_null($photo) ? 'sendPhoto' : 'sendMessage';
        $payload = [
                "chat_id" => $this->chatId,
            ] + ($photo ? ['photo' => $photo, 'caption' => $message, 'parse_mode' => 'Markdown'] : ['text' => $message]);
        $response = Http::post($this->chatUrl . $endpint, $payload);
        return $response;
    }
}
