<?php

use App\Models\Branch;
use App\Models\ClinicSetting;
use App\Models\Employee;
use App\Models\Treatment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

// In-app alerts and webhooks (2026-10-24): the office hears about website
// enquiries and online bookings; the owner can send events to their own system.

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('next monday 08:00'));
});

function alertOwner(): User
{
    return User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
}

function enquiry(): array
{
    return ['form' => 'enquiry', 'first_name' => 'Bea', 'phone' => '0917 222 3344', 'message' => 'Do you do HIFU?', 'privacy_consent' => '1'];
}

it('alerts the people who work the leads when a website enquiry comes in', function () {
    $this->post(route('leads.store'), enquiry())->assertRedirect();

    $alert = alertOwner()->unreadNotifications()->sole();
    expect($alert->data['title'])->toBe('New enquiry from Bea')
        ->and($alert->data['body'])->not->toContain('0917');

    $this->actingAs(alertOwner())->get('/admin/notifications')->assertOk()
        ->assertInertia(fn ($page) => $page->where('notifications.0.title', 'New enquiry from Bea')->where('notificationsUnread', 1));
    $this->get(route('admin.notifications.open', $alert->id))->assertRedirect($alert->data['url']);
    expect(alertOwner()->unreadNotifications()->count())->toBe(0);
});

it('tells branch staff only about bookings at their own branch', function () {
    $desk = User::where('email', 'reception@glutarepubliq.com')->firstOrFail();
    Employee::where('user_id', $desk->id)->update(['branch_id' => Branch::where('slug', 'bacoor')->value('id')]);

    $this->post(route('book.store'), [
        'treatment_id' => Treatment::where('slug', 'hydra-facial')->value('id'),
        'branch_id' => Branch::where('slug', 'cebu')->value('id'),
        'date' => now()->toDateString(),
        'time' => '10:00',
        'first_name' => 'Cara',
        'phone' => '0917 333 4455',
        'privacy_consent' => '1',
    ])->assertRedirect();

    expect(alertOwner()->unreadNotifications()->where('data->event', 'appointment.booked')->count())->toBe(1)
        ->and($desk->unreadNotifications()->count())->toBe(0);
});

it('posts signed events to the owner webhook, and only the owner sets it up', function () {
    Http::fake(['hooks.example.com/*' => Http::response('ok', 200)]);

    $this->actingAs(User::where('email', 'reception@glutarepubliq.com')->firstOrFail())
        ->get('/admin/settings/clinic')->assertForbidden();

    $this->actingAs(alertOwner())->patch(route('admin.settings.clinic.update'), [
        'webhook_url' => 'https://hooks.example.com/clinic',
        'webhook_events' => ['lead.created'],
    ])->assertRedirect();
    $secret = ClinicSetting::get('webhooks.secret');
    expect($secret)->not->toBeEmpty();

    $this->post(route('leads.store'), enquiry());

    Http::assertSent(fn (Request $r) => $r->url() === 'https://hooks.example.com/clinic'
        && $r['event'] === 'lead.created'
        && $r['data']['phone'] === '0917 222 3344'
        && $r->header('X-Clinic-Signature')[0] === 'sha256='.hash_hmac('sha256', $r->body(), $secret));
    expect(ClinicSetting::get('webhooks.last'))->toContain('HTTP 200');
});
