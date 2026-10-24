<?php

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Specialist;
use App\Models\Treatment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SiteContentSeeder;

// The menu restarted from the clinic's collaterals (2026-10-24).

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('loads the collateral menu with its sub-categories and exact prices', function () {
    expect(Treatment::count())->toBe(count(SiteContentSeeder::TREATMENTS));

    $hifu = Treatment::where('slug', '9d-hifu')->firstOrFail();
    expect($hifu->price)->toBe(5000.0)
        ->and(collect($hifu->price_list)->firstWhere('label', 'Face & Neck')['price'])->toBe(7000);

    expect(Treatment::where('slug', 'gluta-drip-gold')->value('subcategory'))->toBe('Gluta Push and Gluta Drip');
});

it('keeps unpriced services off the till and quotes them on consultation', function () {
    $owner = User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
    $services = collect($this->actingAs($owner)->get('/admin/pos')->viewData('page')['props']['services']);

    expect($services->pluck('id'))->not->toContain(Treatment::where('slug', 'profhilo')->value('id'))
        ->and($services->pluck('id'))->toContain(Treatment::where('slug', 'hydra-facial')->value('id'));

    expect($this->get('/llms.txt')->getContent())->toContain('Profhilo')->toContain('price on consultation');
});

it('leaves an office price alone when the seeders run again on deploy', function () {
    Treatment::where('slug', 'hydra-facial')->update(['price' => 1750]);

    $this->seed(DatabaseSeeder::class);

    expect((float) Treatment::where('slug', 'hydra-facial')->value('price'))->toBe(1750.0)
        ->and(Treatment::count())->toBe(count(SiteContentSeeder::TREATMENTS));
});

it('keeps a booking in the diary when its service is removed from the menu', function () {
    $treatment = Treatment::where('slug', 'teen-facial')->firstOrFail();
    $appointment = Appointment::create([
        'organization_id' => $treatment->organization_id,
        'reference' => 'APT-RESTART',
        'branch_id' => Branch::where('slug', 'cebu')->value('id'),
        'treatment_id' => $treatment->id,
        'specialist_id' => Specialist::value('id'),
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'status' => 'confirmed',
    ]);

    $treatment->delete();

    expect($appointment->fresh()->treatment_id)->toBeNull();

    $diary = $this->actingAs(User::where('email', 'owner@glutarepubliq.com')->firstOrFail())
        ->get(route('admin.appointments.index', ['date' => now()->addDay()->toDateString()]))
        ->assertOk()->viewData('page')['props']['appointments'];
    expect(collect($diary)->firstWhere('reference', 'APT-RESTART')['treatment'])->toBe('Service no longer offered');
});
