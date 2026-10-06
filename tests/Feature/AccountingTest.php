<?php

use App\Actions\Accounting\PostSale;
use App\Actions\Pos\RingUpSale;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Treatment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function bookkeeper(): User
{
    return User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
}

function reception(): User
{
    return User::where('email', 'reception@glutarepubliq.com')->firstOrFail();
}

function takings(): Sale
{
    $till = reception();
    $branch = Branch::where('slug', 'cebu')->firstOrFail();
    $treatment = Treatment::where('slug', 'hydra-facial')->firstOrFail();
    $product = Product::where('slug', 'cleansing-balm-100ml')->firstOrFail();

    return app(RingUpSale::class)([
        'branch_id' => $branch->id,
        'items' => [
            ['kind' => 'service', 'id' => $treatment->id, 'quantity' => 1],
            ['kind' => 'product', 'id' => $product->id, 'quantity' => 2],
        ],
        'discount_type' => 'percent',
        'discount_value' => 10,
        'method' => 'cash',
        'amount' => 2000,
    ], $till)->sale;
}

function balanceOf(string $code): float
{
    return round(Account::where('code', $code)->firstOrFail()->balance(), 2);
}

/** Gross movement across the whole journal, the yardstick a trial balance is checked against. */
function journalDebits(): float
{
    return round((float) JournalLine::query()->withoutGlobalScopes()->sum('debit'), 2);
}

function journalCredits(): float
{
    return round((float) JournalLine::query()->withoutGlobalScopes()->sum('credit'), 2);
}

it('books a counter sale as a balanced entry', function () {
    $sale = takings();
    $entry = JournalEntry::where('source_type', 'pos_sale')->where('source_id', (string) $sale->id)->firstOrFail();

    $expected = round((float) $sale->total, 2);
    $cost = round((float) Product::where('slug', 'cleansing-balm-100ml')->value('cost') * 2, 2);
    // The discount is a real debit line, so the debits are the whole subtotal
    // (total plus discount) plus the cost of the goods that left the shelf.
    $subtotal = round($expected + (float) $sale->discount_amount, 2);

    expect($entry->isBalanced())->toBeTrue()
        ->and($entry->totalDebit())->toBe(round($subtotal + $cost, 2))
        ->and(balanceOf('1000'))->toBe(2000.0)          // cash taken at the till
        ->and(balanceOf('1200'))->toBe(round($expected - 2000, 2))  // the rest is owed
        ->and(balanceOf('1300'))->toBe(-$cost)           // stock that left the shelf
        ->and(balanceOf('5000'))->toBe($cost);           // and what it cost us
});

it('never posts the same sale twice', function () {
    $sale = takings();

    app(PostSale::class)($sale, bookkeeper());

    expect(JournalEntry::where('source_type', 'pos_sale')->where('source_id', (string) $sale->id)->count())->toBe(1)
        ->and($sale->refresh()->note)->toContain('already been posted');
});

it('refuses an entry that does not balance', function () {
    $this->actingAs(bookkeeper())
        ->post('/admin/accounting/entries', [
            'entry_date' => today()->toDateString(),
            'memo' => 'Unbalanced',
            'lines' => [
                ['account' => '5200', 'debit' => 1000, 'credit' => 0],
                ['account' => '1000', 'debit' => 0, 'credit' => 900],
            ],
        ])
        ->assertSessionHasErrors('lines');

    expect(JournalEntry::where('memo', 'Unbalanced')->exists())->toBeFalse();
});

it('refuses an entry into a closed month', function () {
    $period = AccountingPeriod::forDate(today()->toDateString());
    $period->update(['status' => 'closed']);

    $this->actingAs(bookkeeper())
        ->post('/admin/accounting/entries', [
            'entry_date' => today()->toDateString(),
            'memo' => 'Too late',
            'lines' => [
                ['account' => '5200', 'debit' => 1000, 'credit' => 0],
                ['account' => '1000', 'debit' => 0, 'credit' => 1000],
            ],
        ])
        ->assertSessionHasErrors('entry_date');

    expect(JournalEntry::where('memo', 'Too late')->exists())->toBeFalse();

    $period->update(['status' => 'open']);
    $this->actingAs(bookkeeper())
        ->post('/admin/accounting/entries', [
            'entry_date' => today()->toDateString(),
            'memo' => 'Too late',
            'lines' => [
                ['account' => '5200', 'debit' => 1000, 'credit' => 0],
                ['account' => '1000', 'debit' => 0, 'credit' => 1000],
            ],
        ])
        ->assertSessionHasNoErrors();
});

it('posts a balanced manual entry and can void it', function () {
    $this->actingAs(bookkeeper())
        ->post('/admin/accounting/entries', [
            'entry_date' => today()->toDateString(),
            'memo' => 'August rent',
            'lines' => [
                ['account' => '5200', 'debit' => 45000, 'credit' => 0],
                ['account' => '1000', 'debit' => 0, 'credit' => 45000],
            ],
        ])
        ->assertSessionHasNoErrors();

    $entry = JournalEntry::where('memo', 'August rent')->firstOrFail();
    expect(balanceOf('5200'))->toBe(45000.0);

    $this->actingAs(bookkeeper())
        ->post("/admin/accounting/entries/{$entry->id}/void", ['reason' => 'Wrong month'])
        ->assertRedirect();

    expect($entry->refresh()->isVoid())->toBeTrue()
        ->and(balanceOf('5200'))->toBe(0.0);
});

it('keeps the trial balance and the balance sheet true', function () {
    takings();

    $this->actingAs(bookkeeper())->get('/admin/accounting/reports')->assertOk();

    $debits = round((float) JournalLine::query()->withoutGlobalScopes()->sum('debit'), 2);
    $credits = round((float) JournalLine::query()->withoutGlobalScopes()->sum('credit'), 2);

    expect($debits)->toBe($credits);

    $assets = balanceOf('1000') + balanceOf('1200') + balanceOf('1300');
    $revenue = -(balanceOf('4000') + balanceOf('4010') + balanceOf('4020'));
    $expenses = balanceOf('5000');

    expect(round($assets - ($revenue - $expenses), 2))->toBe(0.0);
});

it('prints a trial balance whose columns add up', function () {
    takings();

    $this->actingAs(bookkeeper())
        ->get('/admin/accounting/reports')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            // The journal balancing is not the same claim as the report
            // balancing: an earlier version normalised every account to its
            // normal side and then split the rows into columns by sign, which
            // printed revenue as a debit and totalled the two columns from
            // different numbers. This asserts what the reader actually sees.
            ->where('trial_totals.debit', fn ($debit) => abs($debit - journalDebits()) < 0.01)
            ->where('trial_totals.credit', fn ($credit) => abs($credit - journalCredits()) < 0.01)
            ->where('trial_totals.debit', fn ($debit) => abs($debit - journalCredits()) < 0.01)
        );
});

it('puts revenue in the credit column and stock in the debit column', function () {
    takings();

    $this->actingAs(bookkeeper())
        ->get('/admin/accounting/reports')
        ->assertOk()
        ->assertInertia(function ($page) {
            $line = fn (string $code) => collect($page->toArray()['props']['trial_balance'])
                ->firstWhere('code', $code);

            $revenue = $line('4000');
            $inventory = $line('1300');

            expect((float) $revenue['debit'])->toBe(0.0)
                ->and($revenue['credit'])->toBeGreaterThan(0.0)
                ->and($inventory['credit'])->toBeGreaterThan(0.0)
                ->and($inventory['normal_balance'])->toBe('debit');
        });
});

it('keeps the books behind the permission', function () {
    $this->actingAs(reception())->get('/admin/accounting')->assertForbidden();
    $this->actingAs(reception())->get('/admin/accounting/entries')->assertForbidden();
    $this->actingAs(reception())->get('/admin/payroll')->assertForbidden();

    $this->actingAs(bookkeeper())->get('/admin/accounting')->assertOk();
    $this->actingAs(bookkeeper())->get('/admin/accounting/accounts')->assertOk();
    $this->actingAs(bookkeeper())->get('/admin/payroll')->assertOk();
});
