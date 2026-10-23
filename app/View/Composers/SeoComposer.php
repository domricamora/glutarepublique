<?php

namespace App\View\Composers;

use App\Models\Branch;
use App\Models\ClinicSetting;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Search and AI visibility for a site with no server-side rendering.
 *
 * The public pages are drawn by React, so the HTML a crawler receives would
 * otherwise carry one generic title and no text. Search engines that run
 * JavaScript cope; the AI answer engines (GPTBot, ClaudeBot, PerplexityBot)
 * mostly do not. This reads the page Inertia is about to send and writes the
 * essentials into the HTML itself: title, description, canonical, social
 * cards, schema.org JSON-LD, and the page's main facts as plain text in a
 * <noscript> block. Nothing here is different from what a visitor sees; it
 * is the same content, readable without JavaScript.
 *
 * Every head tag carries a data-inertia key, so the browser's head manager
 * swaps it for the page's own tags on load rather than duplicating them.
 */
class SeoComposer
{
    public function compose(View $view): void
    {
        $page = $view->getData()['page'] ?? null;
        if (! is_array($page)) {
            return;
        }

        $component = (string) ($page['component'] ?? '');
        // Plain arrays all the way down, whatever the controller handed over.
        $props = json_decode((string) json_encode($page['props'] ?? []), true) ?: [];

        $view->with('seo', $this->forPage($component, $props));
    }

    /**
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     */
    private function forPage(string $component, array $p): array
    {
        $name = (string) config('clinic.name');
        $where = 'Cebu, San Pedro, Sta. Rosa and Bacoor';

        $seo = match (true) {
            $component === 'home' => [
                'title' => "{$name} | Glutathione, laser and skin clinic in {$where}",
                'description' => "{$name}: gluta drips, 9D HIFU, Fotona laser, Hydrafacial, fillers and hair removal at physician-led clinics in {$where}. Prices online, book a consultation.",
                'jsonld' => [$this->clinic(), $this->faqs($p['faqs'] ?? [])],
                'body' => $this->home($p),
            ],
            $component === 'treatments/index' => [
                'title' => "Treatments and prices | {$name}",
                'description' => 'Every treatment with its price, session time and what to expect: facials, laser, injectables, body, hair, wellness drips and makeup.',
                'jsonld' => [$this->breadcrumb(['Treatments' => route('treatments.index')])],
                'body' => $this->treatmentList($p['categories'] ?? []),
            ],
            $component === 'treatments/show' => $this->treatment($p['treatment'] ?? [], $name),
            $component === 'about' => [
                'title' => "About the clinic | {$name}",
                'description' => "How {$name} works: physician-led assessments, written treatment plans, plain pricing, and clinics in {$where}.",
                'jsonld' => [$this->clinic(), $this->faqs($p['faqs'] ?? [])],
                'body' => $this->people('Who looks after you', $p['specialists'] ?? []).$this->branchesHtml(),
            ],
            $component === 'doctors' => [
                'title' => "Doctors and specialists | {$name}",
                'description' => "Meet the doctors and specialists at {$name}: who they are, what they focus on and which branch they work from.",
                'jsonld' => [$this->doctors($p['specialists'] ?? [])],
                'body' => $this->people('Doctors and specialists', $p['specialists'] ?? []),
            ],
            $component === 'contact' => [
                'title' => "Contact, branches and opening hours | {$name}",
                'description' => "Addresses, phone numbers and opening hours for {$name} in {$where}, with an enquiry form that reaches the clinic team.",
                'jsonld' => [$this->clinic()],
                'body' => '<h1>Contact '.e($name).'</h1>'.$this->branchesHtml(),
            ],
            $component === 'membership' => [
                'title' => "Membership | {$name}",
                'description' => "Monthly membership tiers at {$name}: treatment allowances, member pricing and priority booking.",
                'jsonld' => [$this->faqs($p['faqs'] ?? [])],
                'body' => $this->tiers($p['tiers'] ?? []),
            ],
            $component === 'promotions' => [
                'title' => "Promotions and packages | {$name}",
                'description' => "Current {$name} offers and packages, each with what is included and when it ends.",
                'jsonld' => [],
                'body' => $this->promotions($p['promotions'] ?? []),
            ],
            $component === 'before-after' => [
                'title' => "Before and after | {$name}",
                'description' => "Before and after photos from {$name} clients and illustrated comparisons, labelled as illustrations, never as promised results.",
                'jsonld' => [],
                'body' => '<h1>Before and after</h1><p>Client photographs from our clinics and illustrated comparisons. Results vary from person to person; your clinician explains what to expect at your consultation.</p>',
            ],
            $component === 'blog/index' => [
                'title' => "Journal | {$name}",
                'description' => 'Plain-language clinic notes on skin care, aftercare, treatments and what to expect at a consultation.',
                'jsonld' => [],
                'body' => '<h1>Journal</h1><ul>'.collect($p['posts']['data'] ?? [])->map(fn ($post) => '<li><a href="'.e(route('blog.show', $post['slug'])).'">'.e($post['title']).'</a>: '.e($post['excerpt'] ?? '').'</li>')->implode('').'</ul>',
            ],
            $component === 'blog/show' => $this->post($p['post'] ?? [], $name),
            $component === 'legal' => [
                'title' => ($p['page']['title'] ?? 'Policy')." | {$name}",
                'description' => (string) ($p['page']['summary'] ?? ''),
                'jsonld' => [],
                'body' => '<h1>'.e($p['page']['title'] ?? '').'</h1>'.collect($p['page']['sections'] ?? [])->map(fn ($s) => '<h2>'.e($s['heading']).'</h2>'.collect($s['paragraphs'])->map(fn ($x) => '<p>'.e($x).'</p>')->implode(''))->implode(''),
            ],
            $component === 'book' => [
                'title' => "Book a consultation | {$name}",
                'description' => "Book a treatment or consultation at {$name} online: choose the branch, the doctor or first available, and a free time. No account needed.",
                'jsonld' => [],
                'body' => '<h1>Book a consultation</h1><p>Choose a treatment, a branch, a doctor or the first available, and a time. No account needed.</p>'.$this->branchesHtml(),
            ],
            default => null,
        };

        if ($seo === null) {
            // The office, the kiosk and the sign-in pages are not for search.
            return ['noindex' => true];
        }

        // Page-type schemas on top of the page's own, and the site itself on every page.
        $extra = match ($component) {
            'treatments/index' => [$this->catalog($p['categories'] ?? [])],
            'membership' => [$this->tierOffers($p['tiers'] ?? [])],
            'promotions' => [$this->promotionOffers($p['promotions'] ?? [])],
            'about' => [$this->webPage('AboutPage', $seo['title'])],
            'contact' => [$this->webPage('ContactPage', $seo['title'])],
            default => [],
        };
        $seo['jsonld'] = [$this->website(), ...$seo['jsonld'], ...$extra];

        // The branded share card for this page, when one has been built (scripts/og-images.cjs).
        $card = match ($component) {
            'treatments/show' => 'treatment-'.($p['treatment']['slug'] ?? ''),
            'blog/show' => 'post-'.($p['post']['slug'] ?? ''),
            'treatments/index' => 'treatments',
            'blog/index' => 'journal',
            default => $component,
        };

        return [
            'canonical' => url()->current(),
            'image' => $this->card($card) ?? $seo['image'] ?? $this->card('home') ?? asset('media/photos/clinic/cebu-storefront.jpg'),
            'type' => $seo['type'] ?? 'website',
            'site_name' => $name,
        ] + $seo;
    }

    private function card(string $key): ?string
    {
        return is_file(public_path("media/og/{$key}.jpg")) ? asset("media/og/{$key}.jpg") : null;
    }

    /** @return array<string, mixed> */
    private function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => route('home').'#website',
            'name' => config('clinic.name'),
            'url' => route('home'),
            'inLanguage' => 'en-PH',
            'publisher' => ['@id' => route('home').'#clinic'],
        ];
    }

    /** @return array<string, mixed> */
    private function webPage(string $type, string $title): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => $title,
            'url' => url()->current(),
            'isPartOf' => ['@id' => route('home').'#website'],
            'about' => ['@id' => route('home').'#clinic'],
        ];
    }

    /**
     * Every treatment with its starting price, grouped by category.
     *
     * @param  list<array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    private function catalog(array $categories): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'OfferCatalog',
            'name' => 'Treatments and prices at '.config('clinic.name'),
            'url' => route('treatments.index'),
            'itemListElement' => collect($categories)->filter(fn ($c) => ! empty($c['treatments']))->values()->map(fn ($c) => [
                '@type' => 'OfferCatalog',
                'name' => $c['name'],
                'itemListElement' => collect($c['treatments'])->map(fn ($t) => [
                    '@type' => 'Offer',
                    'priceCurrency' => 'PHP',
                    'price' => (float) ($t['promo_price'] ?? $t['price']),
                    'url' => route('treatments.show', $t['slug']),
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $t['name'],
                        'description' => $t['summary'] ?? null,
                        'provider' => ['@id' => route('home').'#clinic'],
                    ],
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tiers
     * @return array<string, mixed>
     */
    private function tierOffers(array $tiers): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'OfferCatalog',
            'name' => 'Membership at '.config('clinic.name'),
            'url' => route('membership'),
            'itemListElement' => collect($tiers)->map(fn ($t) => [
                '@type' => 'Offer',
                'name' => $t['name'].' membership',
                'description' => $t['tagline'] ?? null,
                'priceSpecification' => [
                    '@type' => 'UnitPriceSpecification',
                    'price' => (float) $t['price_monthly'],
                    'priceCurrency' => 'PHP',
                    'unitCode' => 'MON',
                    'referenceQuantity' => ['@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => 'MON'],
                ],
                'seller' => ['@id' => route('home').'#clinic'],
            ])->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $promotions
     * @return array<string, mixed>
     */
    private function promotionOffers(array $promotions): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Promotions at '.config('clinic.name'),
            'itemListElement' => collect($promotions)->values()->map(fn ($x, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => array_filter([
                    '@type' => 'Offer',
                    'name' => $x['title'],
                    'description' => $x['summary'] ?? null,
                    'url' => route('promotions'),
                    'validThrough' => $x['ends_on'] ?? null,
                    'priceCurrency' => isset($x['treatment']['price']) ? 'PHP' : null,
                    'price' => isset($x['treatment']['price']) ? (float) ($x['treatment']['promo_price'] ?? $x['treatment']['price']) : null,
                    'seller' => ['@id' => route('home').'#clinic'],
                ]),
            ])->all(),
        ];
    }

    /** @return array<string, mixed> the clinic and every branch, as one schema.org organisation */
    private function clinic(): array
    {
        $contact = config('clinic.contact');

        return [
            '@context' => 'https://schema.org',
            '@type' => ['MedicalClinic', 'HealthAndBeautyBusiness'],
            '@id' => route('home').'#clinic',
            'name' => config('clinic.name'),
            'description' => config('clinic.tagline').' Physician-led glutathione, laser and aesthetic skin clinic in the Philippines.',
            'url' => route('home'),
            'logo' => asset(config('clinic.logo')),
            'image' => asset('media/photos/clinic/cebu-storefront.jpg'),
            'telephone' => $contact['phone'] ?? null,
            'email' => $contact['email'] ?? null,
            'priceRange' => '₱₱',
            'currenciesAccepted' => 'PHP',
            'paymentAccepted' => 'Cash, Visa, Mastercard, GCash, Maya',
            'medicalSpecialty' => ['Dermatology', 'PlasticSurgery'],
            'sameAs' => array_values(ClinicSetting::socials()),
            'department' => Branch::where('is_active', true)->orderBy('id')->get()->map(fn (Branch $b) => $this->branch($b))->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function branch(Branch $b): array
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return array_filter([
            '@type' => 'MedicalClinic',
            'name' => config('clinic.name').' '.$b->name,
            'telephone' => $b->phone,
            'email' => $b->email,
            'url' => route('contact'),
            'hasMap' => $b->map_url,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $b->address,
                'addressLocality' => $b->city,
                'addressCountry' => 'PH',
            ]),
            'openingHoursSpecification' => collect($b->hours)->map(fn ($slot, $day) => $slot ? [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $days[$day],
                'opens' => $slot[0],
                'closes' => $slot[1],
            ] : null)->filter()->values()->all(),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $faqs
     * @return array<string, mixed>|null
     */
    private function faqs(array $faqs): ?array
    {
        return $faqs === [] ? null : [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($f) => [
                '@type' => 'Question',
                'name' => $f['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
            ], $faqs),
        ];
    }

    /**
     * @param  array<string, string>  $trail  label => url, after Home
     * @return array<string, mixed>
     */
    private function breadcrumb(array $trail): array
    {
        $items = ['Home' => route('home')] + $trail;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->keys()->values()->map(fn ($label, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $label,
                'item' => $items[$label],
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $t
     * @return array<string, mixed>|null
     */
    private function treatment(array $t, string $name): ?array
    {
        if (! isset($t['slug'])) {
            return null;
        }

        $price = (float) ($t['promo_price'] ?? $t['price'] ?? 0);
        $lines = collect($t['price_list'] ?? []);
        $prices = $lines->pluck('price')->map(fn ($v) => (float) $v)->push($price)->filter();
        $url = route('treatments.show', $t['slug']);
        $category = $t['category']['name'] ?? null;

        return [
            'title' => $t['name'].', from ₱'.number_format($price)." | {$name}",
            'description' => Str::limit(trim(($t['summary'] ?? '').' '.$t['duration_minutes'].' minute session from ₱'.number_format($price).'. Book online.'), 158),
            'image' => $t['image'] ?? null,
            'jsonld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $t['name'],
                    'serviceType' => $category ?? 'Aesthetic treatment',
                    'description' => $t['description'] ?? $t['summary'] ?? null,
                    'url' => $url,
                    'image' => $t['image'] ?? null,
                    'provider' => ['@id' => route('home').'#clinic'],
                    'areaServed' => ['Cebu City', 'San Pedro, Laguna', 'Santa Rosa, Laguna', 'Bacoor, Cavite'],
                    'offers' => $prices->count() > 1 ? [
                        '@type' => 'AggregateOffer',
                        'priceCurrency' => 'PHP',
                        'lowPrice' => $prices->min(),
                        'highPrice' => $prices->max(),
                        'offerCount' => $prices->count(),
                        'url' => $url,
                    ] : [
                        '@type' => 'Offer',
                        'priceCurrency' => 'PHP',
                        'price' => $price,
                        'url' => $url,
                    ],
                ],
                $this->breadcrumb(['Treatments' => route('treatments.index'), $t['name'] => $url]),
            ],
            'body' => '<h1>'.e($t['name']).'</h1>'
                .($category ? '<p>Category: '.e($category).'</p>' : '')
                .'<p>'.e($t['description'] ?? $t['summary'] ?? '').'</p>'
                .'<p>Session: '.e($t['duration_minutes']).' minutes. From ₱'.number_format($price).'.'
                .(! empty($t['recommended_sessions']) ? ' Recommended: '.e($t['recommended_sessions']).'.' : '').'</p>'
                .($lines->isNotEmpty() ? '<h2>Prices</h2><ul>'.$lines->map(fn ($l) => '<li>'.e($l['label']).': ₱'.number_format((float) $l['price']).(! empty($l['unit']) ? ' ('.e($l['unit']).')' : '').'</li>')->implode('').'</ul>' : '')
                .$this->section('Preparation', $t['preparation'] ?? null)
                .$this->section('Aftercare', $t['aftercare'] ?? null)
                .$this->section('Who should not have it', $t['contraindications'] ?? null)
                .'<p><a href="'.e(route('book')).'">Book a consultation</a></p>',
        ];
    }

    /**
     * @param  array<string, mixed>  $post
     * @return array<string, mixed>|null
     */
    private function post(array $post, string $name): ?array
    {
        if (! isset($post['slug'])) {
            return null;
        }

        $url = route('blog.show', $post['slug']);

        return [
            'title' => $post['title']." | {$name}",
            'description' => (string) ($post['excerpt'] ?? ''),
            'image' => $post['image'] ?? null,
            'type' => 'article',
            'jsonld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BlogPosting',
                    'headline' => $post['title'],
                    'description' => $post['excerpt'] ?? null,
                    'image' => $post['image'] ?? null,
                    'datePublished' => $post['published_at'] ?? null,
                    'author' => ['@type' => 'Person', 'name' => $post['author_name'] ?? $name],
                    'publisher' => ['@id' => route('home').'#clinic'],
                    'mainEntityOfPage' => $url,
                    'articleSection' => $post['category'] ?? null,
                ],
                $this->breadcrumb(['Journal' => route('blog.index'), $post['title'] => $url]),
            ],
            'body' => '<article><h1>'.e($post['title']).'</h1><p>By '.e($post['author_name'] ?? $name).'</p>'
                .collect($post['paragraphs'] ?? [])->map(fn ($x) => '<p>'.e(is_array($x) ? implode(' ', $x) : $x).'</p>')->implode('')
                .(! empty($post['takeaways']) ? '<h2>Key points</h2><ul>'.collect($post['takeaways'])->map(fn ($x) => '<li>'.e(is_array($x) ? implode(' ', $x) : $x).'</li>')->implode('').'</ul>' : '')
                .'</article>',
        ];
    }

    /** @param  array<string, mixed>  $p */
    private function home(array $p): string
    {
        $html = '<h1>'.e(config('clinic.name')).'</h1><p>'.e(config('clinic.tagline')).' Physician-led glutathione drips, laser, HIFU, facials, injectables and hair removal at clinics in Cebu, San Pedro, Sta. Rosa and Bacoor.</p>';

        if (! empty($p['featured'])) {
            $html .= '<h2>Signature treatments</h2><ul>'.collect($p['featured'])->map(fn ($t) => '<li><a href="'.e(route('treatments.show', $t['slug'])).'">'.e($t['name']).'</a>: '.e($t['summary'] ?? '').' From ₱'.number_format((float) ($t['promo_price'] ?? $t['price'])).'.</li>')->implode('').'</ul>';
        }
        if (! empty($p['faqs'])) {
            $html .= '<h2>Questions</h2>'.collect($p['faqs'])->map(fn ($f) => '<h3>'.e($f['question']).'</h3><p>'.e($f['answer']).'</p>')->implode('');
        }

        return $html.$this->branchesHtml();
    }

    /** @param  list<array<string, mixed>>  $categories */
    private function treatmentList(array $categories): string
    {
        return '<h1>Treatments and prices</h1>'.collect($categories)->map(fn ($c) => '<h2>'.e($c['name']).'</h2>'
            .(! empty($c['description']) ? '<p>'.e($c['description']).'</p>' : '')
            .'<ul>'.collect($c['treatments'] ?? [])->map(fn ($t) => '<li><a href="'.e(route('treatments.show', $t['slug'])).'">'.e($t['name']).'</a>: from ₱'.number_format((float) ($t['promo_price'] ?? $t['price'])).', '.e($t['duration_minutes']).' minutes. '.e($t['summary'] ?? '').'</li>')->implode('').'</ul>')->implode('');
    }

    /** @param  list<array<string, mixed>>  $people */
    private function people(string $heading, array $people): string
    {
        return '<h1>'.e($heading).'</h1><ul>'.collect($people)->map(fn ($s) => '<li><strong>'.e($s['name']).'</strong>, '.e($s['title'] ?? '')
            .(! empty($s['credentials']) ? ' ('.e($s['credentials']).')' : '')
            .(! empty($s['bio']) ? ': '.e($s['bio']) : '')
            .(! empty($s['branches']) ? ' Branches: '.e(implode(', ', (array) $s['branches'])).'.' : '').'</li>')->implode('').'</ul>';
    }

    /**
     * @param  list<array<string, mixed>>  $people
     * @return array<string, mixed>
     */
    private function doctors(array $people): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Doctors and specialists at '.config('clinic.name'),
            'itemListElement' => collect($people)->values()->map(fn ($s, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => array_filter([
                    '@type' => 'Person',
                    'name' => $s['name'],
                    'jobTitle' => $s['title'] ?? null,
                    'description' => $s['bio'] ?? null,
                    'image' => $s['photo'] ?? null,
                    'worksFor' => ['@id' => route('home').'#clinic'],
                ]),
            ])->all(),
        ];
    }

    /** @param  list<array<string, mixed>>  $tiers */
    private function tiers(array $tiers): string
    {
        return '<h1>Membership</h1><ul>'.collect($tiers)->map(fn ($t) => '<li><strong>'.e($t['name']).'</strong>: ₱'.number_format((float) $t['price_monthly']).' a month. '.e($t['tagline'] ?? '')
            .(! empty($t['benefits']) ? ' Includes: '.e(implode('; ', array_map(fn ($b) => is_array($b) ? implode(' ', $b) : $b, (array) $t['benefits']))).'.' : '').'</li>')->implode('').'</ul>';
    }

    /** @param  list<array<string, mixed>>  $promotions */
    private function promotions(array $promotions): string
    {
        return '<h1>Promotions and packages</h1><ul>'.collect($promotions)->map(fn ($x) => '<li><strong>'.e($x['title']).'</strong>: '.e($x['summary'] ?? '')
            .(! empty($x['ends_on']) ? ' Until '.e($x['ends_on']).'.' : '').'</li>')->implode('').'</ul>';
    }

    /** Every branch with its address, phone and opening hours, as text. */
    private function branchesHtml(): string
    {
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        return '<h2>Branches and opening hours</h2><ul>'.Branch::where('is_active', true)->orderBy('id')->get()->map(function (Branch $b) use ($days) {
            $hours = collect([1, 2, 3, 4, 5, 6, 0])->map(fn ($d) => $days[$d].' '.($b->hours[$d] ? $b->hours[$d][0].' to '.$b->hours[$d][1] : 'closed'))->implode(', ');

            return '<li><strong>'.e($b->name).'</strong>: '.e(trim($b->address.', '.$b->city, ', ')).'. Phone '.e((string) $b->phone).'. Open '.e($hours).'.</li>';
        })->implode('').'</ul>';
    }

    private function section(string $heading, ?string $text): string
    {
        return $text ? '<h2>'.e($heading).'</h2><p>'.e($text).'</p>' : '';
    }
}
