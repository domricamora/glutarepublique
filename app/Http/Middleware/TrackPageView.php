<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs a view of a public page for the traffic report.
 *
 * The visitor is a hash of the address, the browser and the date, salted with
 * the app key, so it counts unique visitors within a day. The IP address itself
 * is kept too, at the owner's request, and pruned with the row after 13 months. Staff signed in
 * to the office, bots, the office itself and background requests are skipped.
 */
class TrackPageView
{
    private const SKIP = ['admin', 'admin/*', 'dashboard', 'settings', 'settings/*', 'kiosk', 'kiosk/*', 'login', 'two-factor-challenge',
        'forgot-password', 'reset-password/*', 'book/slots', 'book/confirmed/*', 'chat/*', 'sitemap.xml', 'robots.txt', 'up', 'build/*', 'media/*'];

    private const BOTS = '/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse|facebookexternalhit/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET')
            && $response->getStatusCode() === 200
            && $request->user() === null
            && ! $request->is(...self::SKIP)
            && ! $request->headers->has('X-Inertia-Partial-Data')
            && ! ($request->expectsJson() && ! $request->header('X-Inertia'))
            && ! preg_match(self::BOTS, (string) $request->userAgent())) {
            // Tracking must never break a page.
            rescue(fn () => $this->record($request), report: false);
        }

        return $response;
    }

    private function record(Request $request): void
    {
        $agent = (string) $request->userAgent();
        $referrer = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST) ?: null;
        $external = $referrer && $referrer !== $request->getHost() ? Str::lower(Str::after($referrer, 'www.')) : null;
        // A query value can be an array (?utm_source[]=x); only a plain string counts.
        $tag = fn (string $key, int $max) => is_string($v = $request->query($key)) && $v !== '' ? Str::limit($v, $max, '') : null;

        PageView::create([
            'path' => Str::limit('/'.ltrim($request->path(), '/'), 250, ''),
            'source' => $tag('utm_source', 80) ?? $this->sourceOf($external),
            'medium' => $tag('utm_medium', 80) ?? ($external ? 'referral' : null),
            'campaign' => $tag('utm_campaign', 120),
            'referrer_host' => $external ? Str::limit($external, 120, '') : null,
            'device' => preg_match('/iPad|Tablet/i', $agent) ? 'tablet' : (preg_match('/Mobi|Android|iPhone/i', $agent) ? 'mobile' : 'desktop'),
            'ip_address' => $request->ip(),
            'visitor' => substr(hash_hmac('sha256', $request->ip().'|'.$agent.'|'.now()->toDateString(), (string) config('app.key')), 0, 16),
        ]);
    }

    /** Group the referrers people actually arrive from under one name each. */
    private function sourceOf(?string $host): string
    {
        return match (true) {
            $host === null => 'direct',
            str_contains($host, 'google.') => 'google',
            str_contains($host, 'facebook.') || $host === 'fb.com' || str_ends_with($host, 'fb.me') => 'facebook',
            str_contains($host, 'instagram.') => 'instagram',
            str_contains($host, 'tiktok.') => 'tiktok',
            str_contains($host, 'bing.') => 'bing',
            str_contains($host, 'youtube.') => 'youtube',
            default => $host,
        };
    }
}
