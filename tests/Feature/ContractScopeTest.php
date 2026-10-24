<?php

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Treatment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;

// Features added to close the gaps against the development agreement, Annex A.

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function scopeOwner(): User
{
    return User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
}

function scopeReception(): User
{
    return User::where('email', 'reception@glutarepubliq.com')->firstOrFail();
}

/** @param  list<list<string>>  $rows */
function scopeCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'import');
    $handle = fopen($path, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return $path;
}

it('has no public sign-up', function () {
    $this->get('/register')->assertNotFound();
});

it('lets the owner add a staff account that can then sign in, limited to its role', function () {
    $this->actingAs(scopeOwner())->post(route('admin.accounts.store'), [
        'name' => 'Ana Cashier',
        'email' => 'ana@glutarepubliq.com',
        'role' => 'Cashier',
        'password' => 'a-long-first-password',
    ])->assertRedirect()->assertSessionHas('success');

    $ana = User::where('email', 'ana@glutarepubliq.com')->firstOrFail();
    expect($ana->hasRole('Cashier'))->toBeTrue()
        ->and($ana->organization_id)->toBe(scopeOwner()->organization_id)
        ->and(AuditLog::where('action', 'settings.account.create')->exists())->toBeTrue();

    auth()->logout();
    $this->post(route('login.store'), ['email' => 'ana@glutarepubliq.com', 'password' => 'a-long-first-password'])
        ->assertRedirect();
    $this->assertAuthenticatedAs($ana);

    $this->get(route('admin.pos.index'))->assertOk();
    $this->get(route('admin.accounting.index'))->assertForbidden();
    $this->get(route('admin.accounts.index'))->assertForbidden();
});

it('keeps Super Admin out of the roles a clinic can hand out', function () {
    $this->actingAs(scopeOwner())->post(route('admin.accounts.store'), [
        'name' => 'Sneaky', 'email' => 'sneaky@example.com', 'role' => 'Super Admin', 'password' => 'a-long-first-password',
    ])->assertSessionHasErrors('role');
});

it('signs a switched-off account out on its next request', function () {
    $reception = scopeReception();
    $this->actingAs(scopeOwner())->patch(route('admin.accounts.update', $reception), [
        'name' => $reception->name, 'role' => 'Receptionist', 'disabled' => true,
    ])->assertSessionHasNoErrors();

    expect($reception->fresh()->disabled_at)->not->toBeNull();

    $this->actingAs($reception->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});

it('will not let the owner switch off or demote themselves', function () {
    $owner = scopeOwner();
    $this->actingAs($owner)->patch(route('admin.accounts.update', $owner), [
        'name' => $owner->name, 'role' => 'Receptionist', 'disabled' => false,
    ])->assertSessionHasErrors('role');

    expect($owner->fresh()->hasRole('Organization Owner'))->toBeTrue();
});

it('books inside the hours saved on the branch', function () {
    $branch = Branch::where('slug', 'bacoor')->firstOrFail();
    $treatment = Treatment::where('is_active', true)->orderBy('duration_minutes')->firstOrFail();
    $sunday = CarbonImmutable::now()->next(CarbonImmutable::SUNDAY)->toDateString();
    $slots = fn () => $this->getJson(route('book.slots', ['treatment_id' => $treatment->id, 'branch_id' => $branch->id, 'date' => $sunday]))->json();

    expect($slots()['open'])->toBeFalse();

    $hours = $branch->hours;
    $hours[0] = ['11:00', '15:00'];
    $this->actingAs(scopeOwner())->patch(route('admin.branches.update', $branch), [
        'name' => $branch->name, 'is_active' => true, 'hours' => $hours,
    ])->assertSessionHasNoErrors();

    auth()->logout();
    $open = $slots();
    expect($open['open'])->toBeTrue()
        ->and($open['slots'][0] ?? null)->toBe('11:00');
});

it('rejects a branch day that closes before it opens', function () {
    $branch = Branch::where('slug', 'cebu')->firstOrFail();
    $hours = $branch->hours;
    $hours[1] = ['18:00', '09:00'];

    $this->actingAs(scopeOwner())->patch(route('admin.branches.update', $branch), [
        'name' => $branch->name, 'is_active' => true, 'hours' => $hours,
    ])->assertSessionHasErrors('hours.1');
});

it('publishes a legal page edited in the office', function () {
    $page = Page::where('slug', 'terms')->firstOrFail();

    $this->actingAs(scopeOwner())->patch(route('admin.pages.update', $page), [
        'title' => 'Terms of use',
        'summary' => 'The rules.',
        'sections' => [['heading' => 'Bookings', 'paragraphs' => ['Arrive ten minutes early.', 'Cancel a day ahead.']]],
    ])->assertSessionHasNoErrors();

    $this->actingAs(scopeReception())->get(route('admin.pages.index'))->assertForbidden();

    auth()->logout();
    $this->get('/terms')->assertOk()->assertInertia(fn ($p) => $p
        ->where('page.sections.0.heading', 'Bookings')
        ->where('page.sections.0.paragraphs.1', 'Cancel a day ahead.'));
});

it('sends the old team page to About, which shows the team', function () {
    $this->get('/doctors')->assertRedirect('/about');
    expect($this->get('/sitemap.xml')->getContent())->not->toContain('/doctors');
});

it('exports the financial reports and the sales as CSV', function () {
    $reports = $this->actingAs(scopeOwner())->get(route('admin.accounting.reports.export'));
    $reports->assertOk();
    expect($reports->headers->get('content-type'))->toStartWith('text/csv')
        ->and($reports->streamedContent())->toContain('Trial balance')->toContain('Balance sheet');

    $sales = $this->get(route('admin.pos.sales.export'));
    $sales->assertOk();
    expect($sales->streamedContent())->toStartWith('Reference,Date,Branch');

    $this->actingAs(scopeReception())->get(route('admin.accounting.reports.export'))->assertForbidden();
});

it('writes sign-ins and failed attempts to the audit log', function () {
    $this->post(route('login.store'), ['email' => 'owner@glutarepubliq.com', 'password' => 'wrong-password']);
    expect(AuditLog::where('action', 'auth.failed')->where('description', 'like', '%owner@glutarepubliq.com%')->exists())->toBeTrue();

    $this->post(route('login.store'), ['email' => 'owner@glutarepubliq.com', 'password' => config('clinic.demo_password')]);
    expect(AuditLog::where('action', 'auth.login')->exists())->toBeTrue();
});

it('imports existing clients from a CSV, skipping ones already on file', function () {
    $csv = scopeCsv([
        ['first_name', 'last_name', 'email', 'phone', 'branch', 'notes', 'marketing_consent'],
        ['Maria', 'Santos', 'maria@example.com', '0917 000 0001', 'cebu', 'Prefers mornings', 'yes'],
        ['Maria', 'Santos', 'maria@example.com', '0917 000 0001', 'cebu', '', ''],
        ['Jose', 'Reyes', '', '0917 000 0002', 'Bacoor', '', 'no'],
    ]);

    $this->artisan('clinic:import', ['type' => 'clients', 'file' => $csv, '--dry-run' => true])->assertSuccessful();
    expect(Lead::where('form', 'import')->count())->toBe(0);

    $this->artisan('clinic:import', ['type' => 'clients', 'file' => $csv])->assertSuccessful();
    expect(Lead::where('form', 'import')->count())->toBe(2)
        ->and(Lead::where('email', 'maria@example.com')->first()->marketing_consent)->toBeTrue()
        ->and(Lead::where('first_name', 'Jose')->first()->branch?->slug)->toBe('bacoor');

    unlink($csv);
});

it('imports opening stock as dated lots, and saves nothing when a line is bad', function () {
    $product = Product::firstOrFail();
    $before = ProductBatch::count();

    $bad = scopeCsv([
        ['product', 'branch', 'quantity', 'lot', 'expires_on', 'cost'],
        [$product->name, 'cebu', '5', 'LOT-1', '2027-06-30', '100'],
        ['Not a product', 'cebu', '5', '', '', ''],
    ]);
    $this->artisan('clinic:import', ['type' => 'stock', 'file' => $bad])->assertFailed();
    expect(ProductBatch::count())->toBe($before);

    $good = scopeCsv([
        ['product', 'branch', 'quantity', 'lot', 'expires_on', 'cost'],
        [$product->name, 'cebu', '5', 'LOT-1', '2027-06-30', '100'],
    ]);
    $this->artisan('clinic:import', ['type' => 'stock', 'file' => $good])->assertSuccessful();
    expect(ProductBatch::where('lot_number', 'LOT-1')->first()?->expires_on?->toDateString())->toBe('2027-06-30');

    unlink($bad);
    unlink($good);
});
