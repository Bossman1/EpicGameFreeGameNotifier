<?php

use App\Console\Commands\MakeHttpRequest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:epicgames')->everyFourHours();
Schedule::command('app:jobsge')->everyThreeHours();
//Schedule::command('app:eft')->daily(); //linode ip is blocked

