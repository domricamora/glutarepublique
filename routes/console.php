<?php

use Illuminate\Support\Facades\Schedule;

// Needs one cron line on the host: * * * * * php /path/to/artisan schedule:run
Schedule::command('clinic:backup')->dailyAt('02:00')->timezone('Asia/Manila')->withoutOverlapping();

// Shelf counts, lots and the stock ledger checked against each other; disagreements wait on the inventory page.
Schedule::command('stock:reconcile')->dailyAt('01:30')->timezone('Asia/Manila')->withoutOverlapping();

// Website traffic older than 13 months (App\Models\PageView::prunable).
Schedule::command('model:prune')->dailyAt('03:00')->timezone('Asia/Manila');

// Visitor location from the local DB-IP database (no IP leaves the server); a new edition monthly.
Schedule::command('traffic:locate')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('traffic:geoip-update')->monthlyOn(3, '04:00')->timezone('Asia/Manila');
