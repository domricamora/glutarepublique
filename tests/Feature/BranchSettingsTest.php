<?php

use App\Models\Approval;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\Treatment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;

// Settings each branch keeps for itself (2026-10-24): online booking, receipt
// details, the discount approval limit and a low-stock default. Empty means
// the clinic-wide setting.

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function settingsOwner(): User
{
    return User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
}

function settingsCebu(): Branch
{
    return Branch::where('slug', 'cebu')->firstOrFail();
}

function saveBranch(Branch $branch, array $settings): void
{
    test()->actingAs(settingsOwner())->patch(route('admin.branches.update', $branch), [
        'name' => $branch->name, 'is_active' => true, 'hours' => $branch->hours,
    ] + $settings)->assertSessionHasNoErrors();
}

it('saves a branch\'s own settings', function () {
    saveBranch(settingsCebu(), [
        'online_booking' => false,
        'receipt_name' => 'Gluta RepubliQ Cebu Inc.',
        'receipt_tin' => '123-456-789-000',
        'discount_limit' => 20,
        'low_stock_level' => 5,
    ]);

    $branch = settingsCebu();
    expect($branch->online_booking)->toBeFalse()
        ->and($branch->receipt_tin)->toBe('123-456-789-000')
        ->and($branch->discountLimit())->toBe(20.0)
        ->and($branch->lowStockLevel(0))->toBe(5)
        ->and($branch->lowStockLevel(8))->toBe(8);
});

it('stops online booking at a branch that has switched it off', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('next monday 08:00'));
    $branch = settingsCebu();
    $treatment = Treatment::where('slug', 'hydra-facial')->firstOrFail();
    saveBranch($branch, ['online_booking' => false]);
    auth()->logout();

    $this->get('/book')->assertInertia(fn ($page) => $page
        ->where('branches', fn ($branches) => collect($branches)->doesntContain('id', $branch->id)));
    $this->getJson('/book/slots?'.http_build_query(['treatment_id' => $treatment->id, 'branch_id' => $branch->id, 'date' => now()->toDateString()]))
        ->assertNotFound();
    $this->post('/book', [
        'treatment_id' => $treatment->id, 'branch_id' => $branch->id, 'date' => now()->toDateString(), 'time' => '10:00',
        'first_name' => 'Ana', 'phone' => '0917 123 4567', 'privacy_consent' => '1',
    ])->assertSessionHasErrors('branch_id');
});

it('lets a branch give a bigger discount before the owner must approve', function () {
    $desk = User::where('email', 'reception@glutarepubliq.com')->firstOrFail();
    $cart = fn () => [
        'branch_id' => settingsCebu()->id,
        'items' => [['kind' => 'service', 'id' => Treatment::where('slug', 'hydra-facial')->value('id'), 'quantity' => 1]],
        'discount_type' => 'percent', 'discount_value' => 15, 'method' => 'cash',
    ];

    $this->actingAs($desk)->post('/admin/pos', $cart());
    expect(Approval::count())->toBe(1)->and(Sale::count())->toBe(0);

    saveBranch(settingsCebu(), ['discount_limit' => 20]);
    $this->actingAs($desk)->post('/admin/pos', $cart())->assertSessionHasNoErrors();

    expect(Approval::count())->toBe(1)->and(Sale::count())->toBe(1);
});

it('calls stock low at the branch default when a product has no level of its own', function () {
    $branch = settingsCebu();
    $product = Product::where('slug', 'vitamin-c-serum-30ml')->firstOrFail();
    ProductStock::where('product_id', $product->id)->where('branch_id', $branch->id)->update(['reorder_level' => 0]);
    $onHand = (int) ProductStock::where('product_id', $product->id)->where('branch_id', $branch->id)->value('on_hand');
    $status = fn () => collect($this->actingAs(settingsOwner())->get('/admin/inventory?branch='.$branch->id)
        ->viewData('page')['props']['rows'])->firstWhere('id', $product->id)['status'];

    expect($status())->not->toBe('low');

    saveBranch($branch, ['low_stock_level' => $onHand]);

    expect($status())->toBe('low');
});

it('prints the branch\'s receipt details on its sales', function () {
    saveBranch(settingsCebu(), ['receipt_name' => 'Gluta RepubliQ Cebu Inc.', 'receipt_tin' => '123-456-789-000', 'receipt_footer' => 'Salamat po!']);
    $this->post('/admin/pos', [
        'branch_id' => settingsCebu()->id,
        'items' => [['kind' => 'service', 'id' => Treatment::where('slug', 'hydra-facial')->value('id'), 'quantity' => 1]],
        'discount_type' => 'none', 'method' => 'cash',
    ])->assertSessionHasNoErrors();

    $this->get(route('admin.pos.sales.show', Sale::sole()))->assertInertia(fn ($page) => $page
        ->where('sale.receipt.receipt_tin', '123-456-789-000')
        ->where('sale.receipt.receipt_footer', 'Salamat po!'));
});
