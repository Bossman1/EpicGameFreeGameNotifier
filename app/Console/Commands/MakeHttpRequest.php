<?php

namespace App\Console\Commands;

use App\Mail\SendMail;
use App\Models\RecordGameInfo;
use App\Services\SmsService;
use App\Services\VonageService;
use Illuminate\Console\Command;
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
            $dataArray = [];
            if (isset($data['data']['Catalog']['searchStore']['elements'][0])) {
                $data = $data['data']['Catalog']['searchStore']['elements'][0];
                $imageArray = [];
                $dataArray['game_id'] = $data['id'] ?? null;
                $dataArray['game_title'] = $data['title'] ?? null;
                $dataArray['game_description'] = $data['description'] ?? null;
                $dataArray['game_effective_date'] = $data['effectiveDate'] ?? null;
                $dataArray['game_seller'] = $data['seller']['name'] ?? null;
                $imageArray['images']['DieselStoreFrontWide'] = $data['keyImages'][1]['url'] ?? null;
                $imageArray['images']['DieselStoreFrontTall'] = $data['keyImages'][0]['url'] ?? null;
                $imageArray['images']['OfferImageTall'] = $data['keyImages'][2]['url'] ?? null;
                $imageArray['images']['CodeRedemption_340x440'] = $data['keyImages'][4]['url'] ?? null;
                $imageArray['images']['Thumbnail'] = $data['keyImages'][5]['url'] ?? null;
                $imageJson = json_encode($imageArray['images']);
                $dataArray = array_merge($dataArray, ['game_images' => $imageJson]);
            }
            $data = RecordGameInfo::where('game_id', $dataArray['game_id'])->count();
            if ($data <= 0) {
                $subject = 'Epic Games Free Game';
                if (RecordGameInfo::create($dataArray)) {
//                if (true) {
                    // send mail notification
                    Mail::to(['nikakharadze82@gmail.com', 'nelitabidze@gmail.com'])->send(new SendMail($subject, $dataArray));


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
