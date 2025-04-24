<?php

namespace App\Console\Commands;

use App\Models\JobsGe;
use App\Services\MessageService;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

class HrGeRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hrge';

    private $dataObject;

    private $subject;

    protected NotificationService $notificationService;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve new job';


    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
        $this->subject = "New Job in hr.ge";
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $this->getData()->sendNotifications();
        $this->getData();

    }


    private function getdata()
    {


        $client = new HttpBrowser(HttpClient::create([
            'timeout' => 5,
            'max_redirects' => 0,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; LaravelBot/1.0)',
                'Accept' => 'text/html,application/xhtml+xml',
            ]
        ]));

        $crawler = $client->request('GET', 'https://www.hr.ge/search-posting?q=&t=1&l=%5B%222%22%5D&c=%5B%22219%22,%22215%22,%22222%22,%22223%22%5D&s=0&os=false&w=false&ef=null&et=null&we=false&ee=false&pg=1&cc=202');

        $results = [];

        $crawler->filter('.container.container--without-large-size.ng-star-inserted')->each(function ($container) use (&$results) {
            $linkNode = $container->filter('.content.ann-tile .title.title-link--without-large-size');
            $textNode = $container->filter('.content.ann-tile .title--bold-desktop.title__text.ng-star-inserted');
            $companyNode = $container->filter('.company.company--without-large-size .company__title.ng-star-inserted');

            $link = $linkNode->count() ? $linkNode->attr('href') : null;
            $text = $textNode->count() ? $textNode->text() : null;
            $company = $companyNode->count() ? $companyNode->text() : null;

            $results[] = [
                'link' => $link,
                'text' => $text,
                'company' => $company,
            ];
        });


        dd($results);


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
                    $message = MessageService::generateSmsMessageForJobsge($this->dataObject);
                    $this->notificationService->sendSms($subject."\n".$message);
                    $this->info("SMS sent successfully!");
                    break;
                case 'telegram':
                    $message = MessageService::generateTelegramMessageForJobsge($this->dataObject);
                    $this->notificationService->sendTelegramMessage($message, null, 'HTML');
                    $this->info("Telegram message sent successfully!");
                    break;
                case 'email':
                    $this->notificationService->sendMail($subject, $data, 'jobsge_notifications');
                    $this->info("Email sent successfully!");
                    break;
            }
        } catch (\Throwable $e) {
            \Log::error("Failed to send {$channel} notification: {$e->getMessage()}");
            $this->error("Failed to send {$channel} notification. Check logs for details.");
        }
    }


}
