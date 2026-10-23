<?php

use Illuminate\Support\Facades\Schedule;

// Needs one cron line on the host: * * * * * php /path/to/artisan schedule:run
Schedule::command('clinic:backup')->dailyAt('02:00')->timezone('Asia/Manila')->withoutOverlapping();
