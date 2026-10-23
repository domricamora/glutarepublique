<?php

use Illuminate\Support\Facades\Schedule;

// Needs one cron line on the host: * * * * * php /path/to/artisan schedule:run
Schedule::command('clinic:backup')->dailyAt('02:00')->timezone('Asia/Manila')->withoutOverlapping();

// Website traffic older than 13 months (App\Models\PageView::prunable).
Schedule::command('model:prune')->dailyAt('03:00')->timezone('Asia/Manila');
