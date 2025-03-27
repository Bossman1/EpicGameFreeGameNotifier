<?php

namespace App\Console\Commands;

use App\Mail\SendMail;
use App\Models\RecordGameInfo;
use App\Services\SmsService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MakeHttpRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:epicgames';

    protected SmsService $smsService;

    protected TelegramService $telegramService;

    private $dataObject;
    private $subject;


    public function __construct(SmsService $smsService, TelegramService $telegramService)
    {
        parent::__construct();
        $this->smsService = $smsService;
        $this->telegramService = $telegramService;
        $this->subject = "New Game";
    }

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch available free game on Epic Game store';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getData()->sendNotifications();
    }

    /**
     * @return void
     */
    private function sendNotifications(): void
    {
        $sms_active = config('services.smsApi.gatewayapi.active');
        $telegram_active = config('services.telegram.active');
        $email_active = config('services.email.active');

        if ($sms_active) {
            $this->sendSms();
        }
        if ($telegram_active) {
            $this->sendTelegramMessage();
        }
        if ($email_active) {
            $this->sendMail();
        }


    }


    /**
     * @return $this|false
     */
    private function getData(): false|static
    {
        $url = 'https://store-site-backend-static.ak.epicgames.com/freeGamesPromotions';
        $response = Http::get($url);
        if ($response->status() == '200') {
            $data = $response->json();

            $dataCollection = array_filter($data['data']['Catalog']['searchStore']['elements'] ?? [], function ($element) {
                return !empty($element['promotions']['promotionalOffers']) && empty($element['promotions']['upcomingPromotionalOffers']) ? $element['promotions']['promotionalOffers'] : [];
            }) ?: [];
            $images = [];

            $dataArray = [];
            $i = 0;
            $currentTime = Carbon::now();
            foreach ($dataCollection as $data) {
                // Build the basic game data using a shorthand array initialization.
                $startDateString = data_get($data, 'promotions.promotionalOffers.0.promotionalOffers.0.startDate');
                $endDateString = data_get($data, 'promotions.promotionalOffers.0.promotionalOffers.0.endDate');
                $offerStart = $startDateString ? Carbon::parse($startDateString)->format('Y-m-d H:i:s') : null;
                $offerEnd = $endDateString ? Carbon::parse($endDateString)->format('Y-m-d H:i:s') : null;


                $offerIsAvailable = $startDateString && $endDateString && $currentTime->between($offerStart, $offerEnd);
                if (!$offerIsAvailable) continue;

                $count = RecordGameInfo::whereGameId($data['id'])->count();
                if ($count > 0) continue;

                $dataArray[$i] = [
                    'game_id' => $data['id'] ?? null,
                    'game_title' => $data['title'] ?? null,
                    'game_description' => $data['description'] ?? null,
                    'game_effective_date' => $data['effectiveDate'] ?? null,
                    'game_seller' => $data['seller']['name'] ?? null,
                    'game_offer_start' => $offerStart,
                    'game_offer_end' => $offerEnd,
                ];


                // Loop through the mapping to build the images array.
                foreach ($data['keyImages'] as $key => $img) {
                    $images[$img['type']] = $img['url'] ?? null;
                }

                // Merge the JSON encoded images into dataArray.
                $dataArray[$i]['game_images'] = json_encode($images);

                RecordGameInfo::create($dataArray[$i]);

                $i++;
            }
            $this->dataObject = $dataArray;
            return $this;
        }
        return false;
    }

    /**
     * @return void
     */
    private function sendSms(): void
    {
        if (empty($this->dataObject)) return;
        $recipients = explode(",", config('services.smsApi.gatewayapi.recipients'));
        $message = $this->subject . ":\n";

        foreach ($this->dataObject as $obj) {
            $message .= "Title: {$obj['game_title']} \n";
        }
        try {
            $status = $this->smsService->sendSms($recipients, $message);
            if (!is_null($status)) {
                $this->info("SMS sent successfully!");
            } else {
                $this->error("Failed to send SMS. Status: $status");
            }
        } catch (\Exception $e) {
            $this->error("An error occurred while sending SMS: " . $e->getMessage());
        }
    }

    /**
     * @return void
     */
    private function sendTelegramMessage(): void
    {
        if (empty($this->dataObject)) return;
        foreach ($this->dataObject as $k => $obj) {
            try {
                $message = $this->subject . ": Title: {$obj['game_title']} \nDescription: {$obj['game_description']}";
                $gameImages = json_decode($obj['game_images'], true);
                $image = $gameImages['Thumbnail'] ?? reset($gameImages);
                $this->telegramService->sendTelegramMessage($message, $image);
                $this->info("Telegram message sent successfully!");
            } catch (\Exception $e) {
                \Log::error('Telegram message send failed: ' . $e->getMessage());
            }
        }


    }

    /**
     * @return void
     */
    private function sendMail(): void
    {
        if (empty($this->dataObject)) return;
        // send mail notification
        $mail = Mail::to(['nelitabidze@gmail.com'])->send(new SendMail($this->subject, $this->dataObject));
        if ($mail) {
            $this->info("Email sent successfully!");
        } else {
            $this->error("Email sent failure!");
        }
    }
}
