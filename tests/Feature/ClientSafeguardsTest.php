<?php

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\Treatment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

// Conflict-of-interest safeguards (2026-10-24): client contacts are masked for
// everyone but the owner, revealed one client at a time with an audit entry,
// and only the owner exports the client list.

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->lead = Lead::create([
        'organization_id' => User::where('email', 'owner@glutarepubliq.com')->value('organization_id'),
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'email' => 'ana.reyes@example.com',
        'phone' => '0917 555 1234',
        'source' => 'website',
        'stage' => 'new',
    ]);
});

function safeOwner(): User
{
    return User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
}

function safeDesk(): User
{
    return User::where('email', 'reception@glutarepubliq.com')->firstOrFail();
}

it('masks client contacts for staff and shows them to the owner', function () {
    $row = fn (User $user) => collect($this->actingAs($user)->get('/admin/leads')->viewData('page')['props']['leads']['data'])
        ->firstWhere('id', $this->lead->id);

    expect($row(safeDesk()))->toMatchArray(['phone' => '0917 ••• ••34', 'email' => 'a•••@example.com', 'masked' => true])
        ->and($row(safeOwner()))->toMatchArray(['phone' => '0917 555 1234', 'email' => 'ana.reyes@example.com', 'masked' => false]);
});

it('reveals one client at a time and logs who looked', function () {
    $this->actingAs(safeDesk())->postJson(route('admin.leads.reveal', $this->lead))
        ->assertOk()
        ->assertJson(['phone' => '0917 555 1234', 'email' => 'ana.reyes@example.com']);

    expect(AuditLog::where('action', 'clients.reveal')->where('user_id', safeDesk()->id)->exists())->toBeTrue();
});

it('lets only the owner export the client list, and logs it', function () {
    $this->actingAs(safeDesk())->get(route('admin.leads.export'))->assertForbidden();

    $csv = $this->actingAs(safeOwner())->get(route('admin.leads.export'));
    expect($csv->streamedContent())->toContain('0917 555 1234')
        ->and(AuditLog::where('action', 'clients.export')->exists())->toBeTrue();
});

it('keeps the real number on a sale when the till was given a masked one', function () {
    $service = Treatment::where('price', '>', 0)->where('is_active', true)->firstOrFail();

    $this->actingAs(safeDesk())->post('/admin/pos', [
        'branch_id' => Branch::where('slug', 'cebu')->value('id'),
        'items' => [['kind' => 'service', 'id' => $service->id, 'quantity' => 1]],
        'discount_type' => 'none',
        'method' => 'cash',
        'lead_id' => $this->lead->id,
        'client_name' => 'Ana Reyes',
        'client_phone' => '0917 ••• ••34',
    ])->assertRedirect();

    expect(Sale::latest('id')->value('client_phone'))->toBe('0917 555 1234');
});
