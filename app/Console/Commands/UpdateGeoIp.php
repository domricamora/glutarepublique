<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Downloads this month's DB-IP "IP to City Lite" database (CC BY 4.0, credited
 * on the traffic page) into storage. A new edition comes out each month.
 */
class UpdateGeoIp extends Command
{
    protected $signature = 'traffic:geoip-update';

    protected $description = 'Download the free DB-IP city database used to locate website visitors';

    public static function path(): string
    {
        return storage_path('app/geoip/dbip-city-lite.mmdb');
    }

    public function handle(): int
    {
        File::ensureDirectoryExists(dirname(self::path()));
        $gz = self::path().'.gz';

        // This month's edition, else last month's if it is not published yet.
        foreach ([now(), now()->subMonthNoOverflow()] as $month) {
            $url = 'https://download.db-ip.com/free/dbip-city-lite-'.$month->format('Y-m').'.mmdb.gz';
            if (rescue(fn () => Http::timeout(300)->sink($gz)->get($url)->successful(), false)) {
                $in = gzopen($gz, 'rb');
                $out = fopen(self::path().'.tmp', 'wb');
                while (! gzeof($in)) {
                    fwrite($out, (string) gzread($in, 1 << 20));
                }
                gzclose($in);
                fclose($out);
                rename(self::path().'.tmp', self::path());
                File::delete($gz);
                $this->info('Installed '.basename($url));

                return self::SUCCESS;
            }
        }

        File::delete($gz);
        $this->error('Could not download the DB-IP database.');

        return self::FAILURE;
    }
}
