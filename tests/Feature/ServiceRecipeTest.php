<?php

use App\Actions\Dashboard\ClinicAnalytics;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\JournalLine;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\Specialist;
use App\Models\Treatment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;

// Service recipes and staff (2026-10-24): the products a service uses come off
// the branch's shelf when it is rung up, their cost is booked, and only the
// staff named on a service are offered for it.

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function recipeOwner(): User
{
    return User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
}

function recipeCebu(): Branch
{
    return Branch::where('slug', 'cebu')->firstOrFail();
}

function recipeShelf(Product $product): int
{
    return (int) ProductStock::where('product_id', $product->id)->where('branch_id', recipeCebu()->id)->value('on_hand');
}

/** Hydrafacial uses 2 of the serum and 1 balm per session. */
function recipeFacial(): array
{
    $facial = Treatment::where('slug', 'hydra-facial')->firstOrFail();
    $serum = Product::where('slug', 'vitamin-c-serum-30ml')->firstOrFail();
    $balm = Product::where('slug', 'cleansing-balm-100ml')->firstOrFail();
    $facial->products()->sync([$serum->id => ['quantity' => 2], $balm->id => ['quantity' => 1]]);

    return [$facial->fresh(), $serum, $balm];
}

function recipeSell(Treatment $service, int $quantity = 1): Sale
{
    test()->actingAs(recipeOwner())->post('/admin/pos', [
        'branch_id' => recipeCebu()->id,
        'items' => [['kind' => 'service', 'id' => $service->id, 'quantity' => $quantity]],
        'discount_type' => 'none',
        'method' => 'cash',
    ])->assertSessionHasNoErrors();

    return Sale::withoutGlobalScopes()->latest('id')->firstOrFail();
}

function recipeBooked(string $side): float
{
    return round((float) JournalLine::query()->withoutGlobalScopes()->whereHas('account', fn ($q) => $q->where('code', '5000'))->sum($side), 2);
}

it('takes a service\'s recipe off the branch shelf and books its cost', function () {
    [$facial, $serum, $balm] = recipeFacial();
    $serumBefore = recipeShelf($serum);
    $balmBefore = recipeShelf($balm);
    $expectedCost = round(2 * (2 * (float) $serum->cost + (float) $balm->cost), 2);

    $sale = recipeSell($facial, 2);
    $line = $sale->items()->sole();

    expect(recipeShelf($serum))->toBe($serumBefore - 4)
        ->and(recipeShelf($balm))->toBe($balmBefore - 2)
        ->and($line->cost)->toBe($expectedCost)
        ->and($line->stock_shortfall)->toBeNull()
        ->and((int) InventoryMovement::where('type', 'service')->where('reference', $sale->reference)->sum('quantity'))->toBe(-6)
        ->and(recipeBooked('debit'))->toBe($expectedCost);
});

it('still rings the service up when the shelf is short, and flags it for a count', function () {
    [$facial, $serum] = recipeFacial();
    $have = recipeShelf($serum);
    $facial->products()->updateExistingPivot($serum->id, ['quantity' => $have + 3]);

    $sale = recipeSell($facial->fresh());
    $shortfall = $sale->items()->sole()->stock_shortfall;

    expect(recipeShelf($serum))->toBe(0)
        ->and($shortfall)->toHaveCount(1)
        ->and($shortfall[0])->toMatchArray(['product_id' => $serum->id, 'needed' => $have + 3, 'taken' => $have]);

    $this->get('/admin/inventory?branch='.recipeCebu()->id)->assertInertia(fn ($page) => $page
        ->where('shortfalls.0.reference', $sale->reference));
});

it('keeps a service\'s used products out of a refund, unlike retail stock', function () {
    [$facial, $serum] = recipeFacial();
    $sale = recipeSell($facial);
    $afterSale = recipeShelf($serum);
    $costBooked = recipeBooked('debit');

    $this->post("/admin/pos/sales/{$sale->id}/refund", ['amount' => $sale->total, 'method' => 'cash', 'reason' => 'Client unhappy'])
        ->assertSessionHasNoErrors();

    expect(recipeShelf($serum))->toBe($afterSale)
        ->and(recipeBooked('credit'))->toBe(0.0)
        ->and(recipeBooked('debit'))->toBe($costBooked);
});

it('counts recipe costs in gross profit on the dashboard', function () {
    [$facial] = recipeFacial();
    $sale = recipeSell($facial);

    $money = (new ClinicAnalytics(30))->toArray()['money'];

    expect($money['cost_of_goods'])->toBe((float) $sale->items()->sole()->cost)
        ->and($money['profit'])->toBe(round($money['revenue'] - $money['cost_of_goods'], 2));
});

it('saves a recipe and the staff who may perform a service', function () {
    $facial = Treatment::where('slug', 'hydra-facial')->firstOrFail();
    $serum = Product::where('slug', 'vitamin-c-serum-30ml')->firstOrFail();
    $ivy = Specialist::where('slug', 'ivy-neri')->firstOrFail();

    $this->actingAs(recipeOwner())->put("/admin/catalog/services/{$facial->id}/recipe", [
        'products' => [['product_id' => $serum->id, 'quantity' => 3]],
        'staff' => [$ivy->id],
    ])->assertSessionHasNoErrors();

    expect((int) $facial->products()->sole()->pivot->quantity)->toBe(3)
        ->and($facial->specialists()->pluck('specialists.id')->all())->toBe([$ivy->id]);

    $this->put("/admin/catalog/services/{$facial->id}/recipe", [
        'products' => [['product_id' => $serum->id, 'quantity' => 1], ['product_id' => $serum->id, 'quantity' => 2]],
        'staff' => [],
    ])->assertSessionHasErrors('products.0.product_id');
});

it('only offers the staff named on a service, and anyone when nobody is named', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('next monday 08:00'));
    $facial = Treatment::where('slug', 'hydra-facial')->firstOrFail();
    $ivy = Specialist::where('slug', 'ivy-neri')->firstOrFail();
    $janet = Specialist::where('slug', 'janet-culango')->firstOrFail();
    $slots = fn (?Specialist $who) => $this->getJson('/book/slots?'.http_build_query(array_filter([
        'treatment_id' => $facial->id,
        'branch_id' => recipeCebu()->id,
        'date' => now()->toDateString(),
        'specialist_id' => $who?->id,
    ])))->json('slots');

    expect($slots($janet))->not->toBeEmpty();

    $facial->specialists()->sync([$ivy->id]);

    expect($slots($janet))->toBeEmpty()
        ->and($slots($ivy))->not->toBeEmpty()
        ->and($slots(null))->not->toBeEmpty();

    $this->post('/book', [
        'treatment_id' => $facial->id,
        'branch_id' => recipeCebu()->id,
        'date' => now()->toDateString(),
        'time' => '10:00',
        'first_name' => 'Ana',
        'phone' => '0917 123 4567',
        'privacy_consent' => '1',
    ]);

    expect(Appointment::withoutGlobalScopes()->latest('id')->value('specialist_id'))->toBe($ivy->id);
});
