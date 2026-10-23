<?php

use App\Models\Treatment;
use Database\Seeders\DatabaseSeeder;

// What a crawler that does not run JavaScript (most AI answer engines) reads.

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('writes the title, description, schema and page text into the HTML of a treatment page', function () {
    $treatment = Treatment::where('is_active', true)->whereNotNull('price_list')->firstOrFail();

    $html = $this->get(route('treatments.show', $treatment->slug))->assertOk()->getContent();

    expect($html)->toContain('<title>'.e($treatment->name).', from ₱')
        ->toContain('name="description"')
        ->toContain('rel="canonical"')
        ->toContain('"@type":"Service"')
        ->toContain('"priceCurrency":"PHP"')
        ->toContain('"@type":"BreadcrumbList"')
        ->toContain('<noscript><h1>'.e($treatment->name).'</h1>');
});

it('describes the clinic and every branch with opening hours on the home page', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('"@type":["MedicalClinic","HealthAndBeautyBusiness"]')
        ->toContain('"openingHoursSpecification"')
        ->toContain('"@type":"FAQPage"')
        ->toContain('Branches and opening hours');
});

it('keeps the office out of search', function () {
    $this->get('/login')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow"', false);
});

it('welcomes the AI search crawlers and points them at the sitemap', function () {
    $robots = $this->get('/robots.txt')->assertOk()->getContent();

    expect($robots)->toContain('User-agent: GPTBot')
        ->toContain('User-agent: PerplexityBot')
        ->toContain('User-agent: ClaudeBot')
        ->toContain('Disallow: /admin')
        ->toContain('Sitemap: ');
});

it('serves an llms.txt with branches, hours and every active treatment price', function () {
    $treatment = Treatment::where('is_active', true)->firstOrFail();

    expect($this->get('/llms.txt')->assertOk()->getContent())
        ->toContain('## Branches and opening hours')
        ->toContain('## Treatments and prices')
        ->toContain($treatment->name);
});
