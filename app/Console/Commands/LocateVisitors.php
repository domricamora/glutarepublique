<?php

namespace App\Console\Commands;

use App\Models\PageView;
use Illuminate\Console\Command;
use MaxMind\Db\Reader;
use Throwable;

/**
 * Fills in country, region and city for page views from their IP address,
 * using the DB-IP Lite database on this server (traffic:geoip-update), so no
 * visitor address is ever sent to anyone else. Runs on the scheduler, never
 * inside a page request.
 */
class LocateVisitors extends Command
{
    protected $signature = 'traffic:locate';

    protected $description = 'Look up the location of website visitors in the local IP database';

    public function handle(): int
    {
        $file = UpdateGeoIp::path();
        if (! is_file($file)) {
            $this->warn('No IP database yet: run traffic:geoip-update.');

            return self::SUCCESS;
        }

        $reader = new Reader($file);
        $done = 0;

        PageView::withoutGlobalScopes()->whereNull('located_at')->whereNotNull('ip_address')
            ->distinct()->limit(5000)->pluck('ip_address')
            ->each(function (string $ip) use ($reader, &$done) {
                try {
                    $hit = $reader->get($ip);
                } catch (Throwable) {
                    $hit = null;
                }
                $name = fn (?array $part) => isset($part['names']['en']) ? mb_substr($part['names']['en'], 0, 80) : null;

                // Private and unknown addresses are marked done too, so they are not looked up again.
                $done += PageView::withoutGlobalScopes()->whereNull('located_at')->where('ip_address', $ip)->update([
                    'country' => $name($hit['country'] ?? null),
                    'region' => $name($hit['subdivisions'][0] ?? null),
                    'city' => $name($hit['city'] ?? null),
                    'located_at' => now(),
                ]);
            });

        $reader->close();
        $this->info("Located {$done} page views.");

        return self::SUCCESS;
    }
}
