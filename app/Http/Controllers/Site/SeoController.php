<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\MembershipTier;
use App\Models\Page;
use App\Models\Post;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use App\View\Composers\SeoComposer;
use Illuminate\Http\Response;

/** Sitemap and robots so search engines can find the public pages (plan.md §85). */
class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('treatments.index'), 'priority' => '0.9'],
            ['loc' => route('book'), 'priority' => '0.9'],
            ['loc' => route('about'), 'priority' => '0.7'],
            ['loc' => route('doctors'), 'priority' => '0.7'],
            ['loc' => route('promotions'), 'priority' => '0.6'],
            ...(MembershipTier::where('is_active', true)->exists() ? [['loc' => route('membership'), 'priority' => '0.7']] : []),
            ['loc' => route('before-after'), 'priority' => '0.6'],
            ['loc' => route('blog.index'), 'priority' => '0.7'],
            ['loc' => route('contact'), 'priority' => '0.7'],
        ])->concat(
            Treatment::where('is_active', true)->orderBy('sort')->get(['slug'])
                ->map(fn (Treatment $t) => ['loc' => route('treatments.show', $t->slug), 'priority' => '0.8'])
        )->concat(
            Post::where('is_published', true)->latest('published_at')->get(['slug', 'updated_at'])
                ->map(fn (Post $p) => ['loc' => route('blog.show', $p->slug), 'priority' => '0.5', 'lastmod' => $p->updated_at?->toDateString()])
        )->concat(
            Page::orderBy('id')->get(['slug'])->map(fn (Page $p) => ['loc' => route('legal', $p->slug), 'priority' => '0.3'])
        )->all();

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** Search and AI answer engines are all welcome on the public site; the office is not for anyone. */
    public function robots(): Response
    {
        $private = ['Disallow: /admin', 'Disallow: /dashboard', 'Disallow: /settings', 'Disallow: /book/confirmed', 'Disallow: /kiosk', 'Disallow: /login'];
        $lines = ['User-agent: *', 'Allow: /', ...$private, ''];

        // Named so a blanket rule added later for training crawlers cannot shut out the ones that cite the clinic.
        foreach (['GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'PerplexityBot', 'ClaudeBot', 'Claude-SearchBot', 'anthropic-ai', 'Google-Extended', 'Bingbot', 'Applebot-Extended'] as $bot) {
            $lines = [...$lines, "User-agent: {$bot}", 'Allow: /', ...$private, ''];
        }

        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * A plain-text summary for AI systems (llmstxt.org): who the clinic is,
     * where and when it is open, every treatment with its price, and the pages
     * worth reading. Built from the same records as the site, so it never drifts.
     */
    public function llms(): Response
    {
        $name = config('clinic.name');
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $out = ["# {$name}", '', '> '.config('clinic.tagline').' Physician-led glutathione drips, laser, HIFU, facials, injectables and hair removal in the Philippines. Prices are in Philippine pesos and are a starting price; every plan begins with a consultation.', ''];

        $out[] = '## Branches and opening hours';
        foreach (Branch::where('is_active', true)->orderBy('id')->get() as $b) {
            $hours = $b->opens_on?->isFuture() ? 'Opening '.$b->opens_on->format('F Y') : collect([1, 2, 3, 4, 5, 6, 0])->map(fn ($d) => $days[$d].' '.($b->hours[$d] ? $b->hours[$d][0].'-'.$b->hours[$d][1] : 'closed'))->implode(', ');
            $out[] = "- {$b->city}: ".rtrim(trim($b->name.', '.$b->address, ', '), '.').". Phone {$b->phone}. {$hours}.";
        }

        $out[] = '';
        $out[] = '## Treatments and prices';
        foreach (TreatmentCategory::orderBy('sort')->with(['treatments' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])->get() as $category) {
            if ($category->treatments->isEmpty()) {
                continue;
            }
            $out[] = "### {$category->name}";
            foreach ($category->treatments as $t) {
                $out[] = "- [{$t->name}](".route('treatments.show', $t->slug).'): '.str_replace('₱', 'PHP ', SeoComposer::from((float) ($t->promo_price ?? $t->price))).", {$t->duration_minutes} min. {$t->summary}";
            }
        }

        $out[] = '';
        $out[] = '## Pages';
        foreach ([
            'Book a consultation online (no account needed)' => route('book'),
            'All treatments and prices' => route('treatments.index'),
            'Our team (nurses, aestheticians and facialists)' => route('doctors'),
            'Promotions and packages' => route('promotions'),
            'Before and after (labelled illustrative)' => route('before-after'),
            'Contact and branches' => route('contact'),
            'Journal' => route('blog.index'),
        ] as $label => $url) {
            $out[] = "- [{$label}]({$url})";
        }

        $out[] = '';
        $out[] = '## Contact';
        $out[] = '- Phone: '.config('clinic.contact.phone');
        $out[] = '- Email: '.config('clinic.contact.email');

        return response(implode("\n", $out)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
