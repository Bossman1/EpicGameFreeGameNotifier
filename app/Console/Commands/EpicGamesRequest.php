<?php

namespace App\Console\Commands;

use App\Mail\SendMail;
use App\Models\EpicGame;
use App\Services\NotificationService;
use App\Services\SmsService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EpicGamesRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:epicgames';

    protected NotificationService $notificationService;

    private $dataObject;
    private $subject;


    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
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

            \DB::beginTransaction();
            try {
                foreach ($dataCollection as $data) {
                    // Build the basic game data using a shorthand array initialization.
                    $startDateString = data_get($data, 'promotions.promotionalOffers.0.promotionalOffers.0.startDate');
                    $endDateString = data_get($data, 'promotions.promotionalOffers.0.promotionalOffers.0.endDate');
                    $offerStart = $startDateString ? Carbon::parse($startDateString)->format('Y-m-d H:i:s') : null;
                    $offerEnd = $endDateString ? Carbon::parse($endDateString)->format('Y-m-d H:i:s') : null;


                    $offerIsAvailable = $startDateString && $endDateString && $currentTime->between($offerStart, $offerEnd);
                    if (!$offerIsAvailable) continue;

                    $count = EpicGame::whereGameId($data['id'])->count();
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

                    $game = EpicGame::create($dataArray[$i]);
                    if (!$game) {
                        \Log::error("Failed to create game record for: {$data['title']}");
                    } else {
                        \Log::info("Successfully created game record for: {$data['title']}");
                    }
                    $i++;
                }
                \DB::commit();
                $this->dataObject = $dataArray;
                return $this;
            } catch (\Throwable $e) {
                // Rollback if any error occurs
                \DB::rollBack();
                \Log::error("Error processing Epic Games data: {$e->getMessage()}");
                return false;
            }

        }
        return false;
    }


    /**
     * @return void
     */
    private function sendNotifications(): void
    {
        if (empty($this->dataObject)) {
            $this->info("No data available to send notifications.");
            return;
        }

        $sms_active = config('services.smsApi.gatewayapi.active');
        $telegram_active = config('services.telegram.active');
        $email_active = config('services.email.active');

        // Send notifications if active
        $this->sendNotificationIfActive($sms_active, 'sms', $this->subject, $this->dataObject);
        $this->sendNotificationIfActive($telegram_active, 'telegram', $this->subject, $this->dataObject);
        $this->sendNotificationIfActive($email_active, 'email', $this->subject, $this->dataObject);
    }


    private function sendNotificationIfActive($isActive, $channel, $subject, $data): void
    {
        if (!$isActive) return;

        try {
            switch ($channel) {
                case 'sms':
                    $this->notificationService->sendSms($subject, $data);
                    $this->info("SMS sent successfully!");
                    break;
                case 'telegram':
                    foreach ($data as $obj) {
                        $message = "{$subject}: Title: {$obj['game_title']} \nDescription: {$obj['game_description']}";
                        $gameImages = json_decode($obj['game_images'], true);
                        $image = $gameImages['Thumbnail'] ?? reset($gameImages) ?? null;
                        $this->notificationService->sendTelegramMessage($message, $image);
                    }
                    $this->info("Telegram message sent successfully!");
                    break;
                case 'email':
                    $this->notificationService->sendMail($subject, $data);
                    $this->info("Email sent successfully!");
                    break;
            }
        } catch (\Throwable $e) {
            \Log::error("Failed to send {$channel} notification: {$e->getMessage()}");
            $this->error("Failed to send {$channel} notification. Check logs for details.");
        }
    }

}
