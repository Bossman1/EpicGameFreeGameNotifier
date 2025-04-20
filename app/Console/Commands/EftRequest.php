<?php

namespace App\Console\Commands;

use App\Models\Eft;
use App\Models\JobsGe;
use App\Services\MessageService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class EftRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:eft';

    private $dataObject;

    private $subject;

    protected NotificationService $notificationService;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve Escape from tarkov package price';


    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->subject = "Escape From Tarkov, New Price Alert!";
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->getData()->sendNotifications();

    }


    private function getdata()
    {
        $packageNames = [
            'expansion_pve' => 'EFT: PvE Expansion',
            'package_preorder_unheard_edition' => 'Escape from Tarkov: Unheard Edition',
            'package_preorder_prepare_for_escape' => 'Escape from Tarkov: Prepare for Escape Edition',
            'package_preorder_left_behind' => 'Escape from Tarkov: Left Behind Edition',
            'package_preorder_standard' => 'Escape from Tarkov: Standard Edition',
        ];


        $browser = new HttpBrowser(HttpClient::create());
        $crawlerExpansions = $browser->request('GET', 'https://www.escapefromtarkov.com/expansions');
//        $ids = ['PVE', 'StashRows', 'Suites'];
        $ids = ['PVE'];
        $expansionPrices = [];

        foreach ($ids as $id) {
            $crawlerExpansions->filter("#{$id} .foot_action .price.inline span")->each(function ($node) use (&$expansionPrices, $id) {
                $rawPrice = $node->text();
                $cleanPrice = preg_replace('/[^0-9.]/', '', $rawPrice);  // remove $ or other symbols
                $expansionPrices['expansion_' . strtolower($id)] = $cleanPrice;
            });
        }

        $crawlerPackages = $browser->request('GET', 'https://www.escapefromtarkov.com/preorder-page/');
        $ids = ['preorder_unheard_edition', 'preorder_prepare_for_escape', 'preorder_left_behind', 'preorder_standard'];
        $packPrices = [];
        foreach ($ids as $id) {
            $crawlerPackages->filter("#{$id} .foot .price.inline span")->each(function ($node) use (&$packPrices, $id) {
                $rawPrice = $node->text();
                $cleanPrice = preg_replace('/[^0-9.]/', '', $rawPrice);  // remove $ or other symbols
                $packPrices['package_' . strtolower($id)] = $cleanPrice;
            });
        }

        $prices = array_merge($expansionPrices, $packPrices);
        try {
            DB::beginTransaction();
            foreach ($prices as $packageName => $price) {
                $existingPrice = Eft::where('package_name', $packageName)->first();
                if ($existingPrice) {
                    if ($existingPrice->price != $price) {
                        $this->dataObject[] = (object)[
                            'package_name' => $packageNames[$packageName] ?? $packageName,
                            'old_price' => $existingPrice->price . '$',
                            'new_price' => $price . '$',
                        ];
                        $existingPrice->update(['price' => $price]);
                    }
                } else {
                    Eft::create([
                        'package_name' => $packageName,
                        'price' => $price,
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Price update failed for EFT: ' . $e->getMessage());
        }


        return $this;
    }


    private function sendNotifications()
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
                    $message = MessageService::generateTelegramMessageForEft($this->dataObject);
                    $this->notificationService->sendSms($subject . "\n" . $message);
                    $this->info("SMS sent successfully!");
                    break;
                case 'telegram':
                    $message = MessageService::generateTelegramMessageForEft($this->dataObject);
                    $this->notificationService->sendTelegramMessage($message, null, 'HTML');
                    $this->info("Telegram message sent successfully!");
                    break;
                case 'email':
                    $this->notificationService->sendMail($subject, $data, 'eft_notifications');
                    $this->info("Email sent successfully!");
                    break;
            }
        } catch (\Throwable $e) {
            \Log::error("Failed to send {$channel} notification: {$e->getMessage()}");
            $this->error("Failed to send {$channel} notification. Check logs for details.");
        }
    }


}
