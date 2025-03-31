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

class JobsGeRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:jobsge';

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
        $this->subject = "New Job";
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
        $browser = new HttpBrowser(HttpClient::create());
        $crawler = $browser->request('GET', 'https://jobs.ge/?page=1&q=&cid=6&lid=1&jid=1&has_salary=1');

        $keys = ['position', 'company', 'start_date', 'end_date', 'link'];
        $newJobs = [];

        $crawler->filter('#job_list_table tr')->each(function ($row) use (&$newJobs, $keys, $browser) {

            $rowData = $row->filter('td')->each(function ($td) {
                $text = trim($td->text());
                return $text;
            });

            $rowDataLink = $row->filter('td > a.vip')->each(function ($td) {
                $link = $td->filter('a.vip')->count() > 0 ? $td->filter('a.vip')->attr('href') : null;
                return "https://jobs.ge" . $link;
            });

            $rowData = array_merge(array_values(array_filter($rowData)), $rowDataLink);
            if (count($rowData) === count($keys)) {
                $jobData = array_combine($keys, $rowData);
                if (!JobsGe::where('link', $jobData['link'])->exists()) {
                    $newJobs[] = $jobData;
                }
            }
        });

        try {
            DB::beginTransaction();
            JobsGe::insert($newJobs);
            DB::commit();
            $this->dataObject = $newJobs;
        } catch (QueryException $e) {
            DB::rollBack();
            \Log::error("Error inserting jobs: " . $e->getMessage());
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
