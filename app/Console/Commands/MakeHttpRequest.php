<?php

namespace App\Console\Commands;

use App\Mail\SendMail;
use App\Models\RecordGameInfo;
use App\Services\SmsService;
use App\Services\VonageService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class MakeHttpRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:epicgames';

    protected SmsService $smsService;
    protected VonageService $vonageService;


    public function __construct(SmsService $smsService, VonageService $vonageService)
    {
        parent::__construct();
        $this->smsService = $smsService;
        $this->vonageService = $vonageService;
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


        $url = 'https://store-site-backend-static.ak.epicgames.com/freeGamesPromotions';
        $response = Http::get($url); // Use Http::post() if needed
        if ($response->status() == '200') {
            $data = $response->json();

            $data = current(array_filter($data['data']['Catalog']['searchStore']['elements'] ?? [], function ($element) {
                return !empty($element['promotions']['promotionalOffers'] ?? []);
            })) ?: [];


            // Build the basic game data using a shorthand array initialization.
            $startDateString = data_get($data, 'promotions.promotionalOffers.0.promotionalOffers.0.startDate');
            $endDateString = data_get($data, 'promotions.promotionalOffers.0.promotionalOffers.0.endDate');

            $dataArray = [
                'game_id' => $data['id'] ?? null,
                'game_title' => $data['title'] ?? null,
                'game_description' => $data['description'] ?? null,
                'game_effective_date' => $data['effectiveDate'] ?? null,
                'game_seller' => $data['seller']['name'] ?? null,
                'game_offer_start' => $startDateString ? Carbon::parse($startDateString)->format('Y-m-d H:i:s') : null,
                'game_offer_end' => $endDateString ? Carbon::parse($endDateString)->format('Y-m-d H:i:s') : null,
            ];

            $currentTime = Carbon::now();
            $offerIsAvailable = false;
            if ($dataArray['game_offer_start'] && $dataArray['game_offer_end']) {
                $offerStart = Carbon::createFromFormat('Y-m-d H:i:s', $dataArray['game_offer_start']);
                $offerEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dataArray['game_offer_end']);
                if ($currentTime->between($offerStart, $offerEnd)) {
                    $offerIsAvailable = true;
                }
            }


            // Loop through the mapping to build the images array.
            $images = [];
            foreach ($data['keyImages'] as $key => $index) {
                $images[$index['type']] = $index['url'] ?? null;
            }

            // Merge the JSON encoded images into dataArray.
            $dataArray['game_images'] = json_encode($images);

            $count = RecordGameInfo::whereGameId($dataArray['game_id'])->count();

            if ($count <= 0 && $offerIsAvailable) {
                $subject = 'Epic Games Free Game';
                if (RecordGameInfo::create($dataArray)) {
                    // send mail notification
                    $mail = Mail::to(['nikakharadze82@gmail.com', 'nelitabidze@gmail.com'])->send(new SendMail($subject, $dataArray));


                    //WhatsApp api was not worked last time
//                    $phone = config('services.vonage.sms_to');
//                    $wsp_message = 'New Game in EpicGames Store: '.$dataArray['game_title'];
//                    $wsp_image = $imageArray['images']['Thumbnail'];
//                    $wsp_response = $this->vonageService->sendWhatsAppMessage($phone, $wsp_message, $wsp_image);
//                    $statusMessage = response()->json($wsp_response)->getStatusCode();
//                    if ($statusMessage == 200) {
//                        $this->info("Request sent! Response: " . $response->status());
//                    }else{
//                        $this->info("Something went wrong!");
//                    }


                    //Sms api
                    $phone = config('services.vonage.sms_to');
                    $sms_message = 'New Game in EpicGames Store: ' . $dataArray['game_title'];
                    $status = $this->smsService->sendSms($phone, $sms_message);
                    if ($status === '0') {
                        $this->info("SMS sent successfully!");
                    } else {
                        $this->error("Failed to send SMS. Status: $status");
                    }


                }
            } else {
                $this->info("Record already exist!");
            }
        }
        return false;

    }
}
