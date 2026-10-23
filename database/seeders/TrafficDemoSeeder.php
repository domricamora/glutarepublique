<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\PageView;
use App\Models\Treatment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ninety days of website visits for the demo traffic report: more on
 * weekends and paydays, mostly phones, Google and Facebook leading. Runs only
 * on a database with no traffic yet, so real visits are never mixed with it.
 * Skipped in tests.
 */
class TrafficDemoSeeder extends Seeder
{
    /** [source, medium, campaign, weight] */
    private const SOURCES = [
        ['google', 'organic', null, 30], ['google', 'cpc', 'search-glutathione', 12], ['facebook', 'social', 'glow-promo', 18],
        ['instagram', 'social', 'glass-skin-reels', 12], ['direct', null, null, 16], ['tiktok', 'social', 'laser-myths', 7],
        ['youtube', 'referral', null, 3], ['bing', 'organic', null, 2],
    ];

    public function run(): void
    {
        if (PageView::withoutGlobalScopes()->exists()) {
            return;
        }

        mt_srand(90);
        $org = Organization::where('slug', config('clinic.organization'))->firstOrFail()->id;
        $paths = ['/', '/', '/', '/treatments', '/treatments', '/promotions', '/book', '/book', '/about', '/doctors', '/contact', '/before-after', '/membership', '/journal'];
        foreach (Treatment::withoutGlobalScopes()->where('is_active', true)->where('is_featured', true)->pluck('slug') as $slug) {
            $paths[] = "/treatments/{$slug}";
        }
        $weights = array_sum(array_column(self::SOURCES, 3));
        $rows = [];

        for ($d = 89; $d >= 0; $d--) {
            $day = CarbonImmutable::today()->subDays($d);
            $busy = ($day->isWeekend() ? 1.3 : 1.0) * (in_array($day->day, [15, 16, 30, 31, 1], true) ? 1.25 : 1.0) * (0.75 + 0.25 * (89 - $d) / 89);
            $visitors = (int) round(mt_rand(55, 85) * $busy);

            for ($v = 0; $v < $visitors; $v++) {
                $pick = mt_rand(1, $weights);
                $chosen = self::SOURCES[0];
                foreach (self::SOURCES as $source) {
                    if (($pick -= $source[3]) <= 0) {
                        $chosen = $source;
                        break;
                    }
                }
                [$source, $medium, $campaign] = $chosen;
                $roll = mt_rand(1, 100);
                $device = $roll <= 74 ? 'mobile' : ($roll <= 94 ? 'desktop' : 'tablet');
                $visitor = substr(md5("{$d}-{$v}"), 0, 16);
                $at = $day->setTime(mt_rand(7, 23), mt_rand(0, 59));

                for ($p = 0, $pages = mt_rand(1, 5); $p < $pages; $p++) {
                    $rows[] = [
                        'organization_id' => $org,
                        'path' => $p === 0 && $campaign ? '/promotions' : $paths[mt_rand(0, count($paths) - 1)],
                        'source' => $source,
                        'medium' => $medium,
                        'campaign' => $p === 0 ? $campaign : null,
                        'referrer_host' => $source === 'direct' ? null : "{$source}.com",
                        'device' => $device,
                        'visitor' => $visitor,
                        'created_at' => $at->addMinutes($p * mt_rand(1, 4)),
                    ];
                }
            }

            if (count($rows) > 2000) {
                DB::table('page_views')->insert($rows);
                $rows = [];
            }
        }

        if ($rows) {
            DB::table('page_views')->insert($rows);
        }
    }
}
