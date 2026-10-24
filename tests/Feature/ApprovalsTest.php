<?php

use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Treatment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

// Refunds, discounts over the limit, payroll approval and leave go through the owner.

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function approvalOwner(): User
{
    return User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
}

function approvalDesk(): User
{
    return User::where('email', 'reception@glutarepubliq.com')->firstOrFail();
}

/** @return array<string, mixed> */
function approvalCart(array $extra = []): array
{
    return $extra + [
        'branch_id' => Branch::where('slug', 'cebu')->value('id'),
        'items' => [['kind' => 'service', 'id' => Treatment::where('is_active', true)->where('price', '>', 0)->orderBy('price')->value('id'), 'quantity' => 1]],
        'method' => 'cash',
    ];
}

it('holds a staff refund until the owner approves it, then pays it out as the requester', function () {
    $this->actingAs(approvalDesk())->post(route('admin.pos.store'), approvalCart())->assertRedirect();
    $sale = Sale::latest('id')->firstOrFail();

    $this->post(route('admin.pos.sales.refund', $sale), ['amount' => 100, 'method' => 'cash', 'reason' => 'Client unhappy'])
        ->assertSessionHas('success');

    expect(Payment::where('sale_id', $sale->id)->where('amount', '<', 0)->exists())->toBeFalse();
    $approval = Approval::sole();
    expect($approval->type)->toBe('refund')->and($approval->status)->toBe('pending');

    // Staff cannot decide their own request.
    $this->post(route('admin.approvals.decide', $approval), ['decision' => 'approve'])->assertForbidden();

    $this->actingAs(approvalOwner())->post(route('admin.approvals.decide', $approval), ['decision' => 'approve'])
        ->assertSessionHasNoErrors();

    $refund = Payment::where('sale_id', $sale->id)->where('amount', '<', 0)->sole();
    expect((float) $refund->amount)->toBe(-100.0)
        ->and($approval->fresh()->status)->toBe('approved')
        ->and(AuditLog::where('action', 'approval.approve')->exists())->toBeTrue();
});

it('needs a reason to reject, and a rejected refund pays nothing', function () {
    $this->actingAs(approvalDesk())->post(route('admin.pos.store'), approvalCart())->assertRedirect();
    $sale = Sale::latest('id')->firstOrFail();
    $this->post(route('admin.pos.sales.refund', $sale), ['amount' => 50, 'method' => 'cash', 'reason' => 'Changed mind']);
    $approval = Approval::sole();

    $this->actingAs(approvalOwner())->post(route('admin.approvals.decide', $approval), ['decision' => 'reject'])->assertSessionHasErrors('note');
    $this->post(route('admin.approvals.decide', $approval), ['decision' => 'reject', 'note' => 'Outside the policy'])->assertSessionHasNoErrors();

    expect($approval->fresh()->status)->toBe('rejected')
        ->and(Payment::where('sale_id', $sale->id)->where('amount', '<', 0)->exists())->toBeFalse();
});

it('rings up a sale with a big discount only once the owner approves it', function () {
    $before = Sale::count();

    $this->actingAs(approvalDesk())->post(route('admin.pos.store'), approvalCart(['discount_type' => 'percent', 'discount_value' => 50]))
        ->assertSessionHas('success');
    expect(Sale::count())->toBe($before);

    $this->actingAs(approvalOwner())->post(route('admin.approvals.decide', Approval::sole()), ['decision' => 'approve'])->assertSessionHasNoErrors();

    $sale = Sale::latest('id')->firstOrFail();
    expect(Sale::count())->toBe($before + 1)
        ->and($sale->discount_type)->toBe('percent')
        ->and($sale->user_id)->toBe(approvalDesk()->id);
});

it('lets a small discount through without asking', function () {
    $before = Sale::count();
    $this->actingAs(approvalDesk())->post(route('admin.pos.store'), approvalCart(['discount_type' => 'percent', 'discount_value' => 5]))->assertRedirect();

    expect(Sale::count())->toBe($before + 1)->and(Approval::count())->toBe(0);
});

it('lets any member of staff file their own leave, and only the owner grants it', function () {
    $desk = approvalDesk();
    $employee = Employee::where('user_id', $desk->id)->firstOrFail();

    $this->actingAs($desk)->post(route('admin.my-leave.store'), [
        'type' => 'annual', 'from_date' => now()->addWeek()->toDateString(), 'to_date' => now()->addWeek()->toDateString(), 'days' => 1, 'reason' => 'Family',
    ])->assertSessionHasNoErrors();

    $leave = LeaveRequest::where('employee_id', $employee->id)->latest('id')->firstOrFail();
    expect($leave->status)->toBe('pending');

    $this->patch(route('admin.hr.leave.review', $leave), ['status' => 'approved'])->assertForbidden();

    $this->actingAs(approvalOwner())->post(route('admin.approvals.decide', Approval::where('type', 'leave')->sole()), ['decision' => 'approve'])
        ->assertSessionHasNoErrors();
    expect($leave->fresh()->status)->toBe('approved')->and($leave->fresh()->reviewed_by)->toBe(approvalOwner()->id);
});

it('keeps the approval right with the owner and away from the clinic administrator role', function () {
    expect(approvalOwner()->can('approvals.decide'))->toBeTrue()
        ->and(approvalDesk()->can('approvals.decide'))->toBeFalse();

    $admin = User::factory()->create(['organization_id' => approvalOwner()->organization_id]);
    $admin->assignRole('Clinic Administrator');
    expect($admin->can('approvals.decide'))->toBeFalse()->and($admin->can('payroll.create'))->toBeTrue();
});
