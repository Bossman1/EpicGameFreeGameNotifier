<?php

namespace App\Services;

use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected SmsService $smsService;
    protected TelegramService $telegramService;

    /**
     * @param SmsService $smsService
     * @param TelegramService $telegramService
     */
    public function __construct(SmsService $smsService, TelegramService $telegramService)
    {
        $this->smsService = $smsService;
        $this->telegramService = $telegramService;
    }

    /**
     * @param $subject
     * @param $data
     * @return bool
     */
    public function sendMail($subject, $data)
    {
        $recipients = explode(',', config('services.email.recipients'));

        if (empty($recipients)) {
            Log::info('Please check recipient list in .env file');
            return false;
        }

        try {
            Mail::to($recipients)->send(new SendMail($subject, $data));
            Log::info("Email sent successfully!");
            return true;
        } catch (\Exception $e) {
            Log::error("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param $message
     * @param $image
     * @return void
     */
    public function sendTelegramMessage($message, $image = null): void
    {
        try {
            $this->telegramService->sendTelegramMessage($message, $image);
            Log::info("Telegram message sent successfully!");
        } catch (\Exception $e) {
            Log::error('Telegram message send failed: ' . $e->getMessage());
        }
    }

    public function sendSms($subject, $data)
    {
        $recipients = explode(",", config('services.smsApi.gatewayapi.recipients'));
        $message = $subject . ":\n";

        foreach ($data as $obj) {
            $message .= "Title: {$obj['game_title']} \n";
        }

        try {
            $status = $this->smsService->sendSms($recipients, $message);
            if (!is_null($status)) {
                Log::info("SMS sent successfully!");
            } else {
                Log::error("Failed to send SMS.");
            }
        } catch (\Exception $e) {
            Log::error("An error occurred while sending SMS: " . $e->getMessage());
        }
    }
}

?>
