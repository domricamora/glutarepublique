<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\PageView;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Website traffic: how many people visited, what they read, where they came
 * from, and the raw log underneath. Counted by the site itself, so it works
 * without a third-party tracker or a cookie banner.
 */
class TrafficController extends Controller
{
    private const WINDOWS = [7, 30, 90];

    public function index(Request $request): Response
    {
        $days = in_array($request->integer('days', 30), self::WINDOWS, true) ? $request->integer('days', 30) : 30;
        $from = CarbonImmutable::today()->subDays($days - 1);
        $views = fn () => PageView::where('created_at', '>=', $from);

        $daily = $views()
            ->selectRaw('DATE(created_at) AS day, COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $group = fn (string $column, int $limit = 8) => $views()
            ->selectRaw("{$column} AS label, COUNT(*) AS views, COUNT(DISTINCT visitor) AS visitors")
            ->whereNotNull($column)
            ->groupBy($column)
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->map(fn ($r) => ['label' => (string) $r->label, 'views' => (int) $r->views, 'visitors' => (int) $r->visitors]);

        // Visitors are counted per day (the hash rotates daily), so a period's total is the sum of its days.
        $visitors = (int) $daily->sum('visitors');
        $pageViews = (int) $daily->sum('views');
        $enquiries = Lead::where('created_at', '>=', $from)->whereIn('source', ['website', 'chat', 'google', 'facebook', 'instagram', 'tiktok'])->count();

        return Inertia::render('admin/traffic/index', [
            'days' => $days,
            'windows' => self::WINDOWS,
            'totals' => [
                'views' => $pageViews,
                'visitors' => $visitors,
                'per_visit' => $visitors ? round($pageViews / $visitors, 1) : null,
                'enquiries' => $enquiries,
                'conversion' => $visitors ? round($enquiries / $visitors * 100, 1) : null,
            ],
            'daily' => collect(range(0, $days - 1))->map(function (int $i) use ($from, $daily) {
                $d = $from->addDays($i);
                $row = $daily->get($d->toDateString());

                return ['label' => $d->format('M j'), 'views' => (int) ($row->views ?? 0), 'visitors' => (int) ($row->visitors ?? 0)];
            }),
            'pages' => $group('path', 10),
            'sources' => $group('source'),
            'campaigns' => $group('campaign'),
            'devices' => $group('device', 3),
            'log' => PageView::latest('id')->limit(50)->get(['id', 'path', 'source', 'campaign', 'referrer_host', 'device', 'ip_address', 'country', 'region', 'city', 'created_at'])
                ->map(fn (PageView $v) => [
                    'id' => $v->id,
                    'path' => $v->path,
                    'source' => $v->source,
                    'campaign' => $v->campaign,
                    'referrer' => $v->referrer_host,
                    'device' => $v->device,
                    'ip' => $v->ip_address,
                    'location' => collect([$v->city, $v->region, $v->country])->filter()->unique()->implode(', ') ?: null,
                    'at' => $v->created_at?->toIso8601String(),
                ]),
            'live' => PageView::where('created_at', '>=', now()->subMinutes(30))->distinct()->count('visitor'),
        ]);
    }
}
