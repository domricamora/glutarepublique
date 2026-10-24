<?php

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Treatment;
use App\Models\User;
use App\Support\BranchAccess;
use Database\Seeders\DatabaseSeeder;

// Branch access (2026-10-24): people with branches.all pick a branch in the
// sidebar; everyone else works at their own branch and cannot look elsewhere.

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function accessOwner(): User
{
    return User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
}

/** The receptionist, tied to a branch through their employee record. */
function accessDesk(): User
{
    $desk = User::where('email', 'reception@glutarepubliq.com')->firstOrFail();
    Employee::where('user_id', $desk->id)->update(['branch_id' => Branch::where('slug', 'cebu')->value('id')]);

    return $desk;
}

function otherBranch(): Branch
{
    return Branch::where('slug', 'bacoor')->firstOrFail();
}

it('lets someone with branches.all choose the branch the screens show', function () {
    $owner = accessOwner();
    expect(BranchAccess::canSeeAll($owner))->toBeTrue();

    $this->actingAs($owner)->post(route('admin.branch.choose'), ['branch_id' => otherBranch()->id])->assertRedirect();

    $this->get('/admin/inventory')->assertInertia(fn ($page) => $page->where('branch.id', otherBranch()->id)
        ->where('branchContext.current', otherBranch()->id)
        ->where('branchContext.canSwitch', true));
    $this->get('/admin/pos')->assertInertia(fn ($page) => $page->where('branch_id', otherBranch()->id));

    $this->post(route('admin.branch.choose'), ['branch_id' => null]);
    $this->get('/admin/appointments')->assertInertia(fn ($page) => $page->where('branch', null));
});

it('keeps staff without branches.all at their own branch', function () {
    $desk = accessDesk();
    $cebu = Branch::where('slug', 'cebu')->value('id');

    expect(BranchAccess::canSeeAll($desk))->toBeFalse()
        ->and(BranchAccess::home($desk))->toBe($cebu);

    $this->actingAs($desk)->post(route('admin.branch.choose'), ['branch_id' => otherBranch()->id])->assertForbidden();

    // Asking for another branch in the address bar shows their own.
    $this->get('/admin/pos?branch='.otherBranch()->id)->assertInertia(fn ($page) => $page->where('branch_id', $cebu)
        ->where('branchContext.canSwitch', false));
    $this->get('/admin/appointments?branch='.otherBranch()->id)->assertInertia(fn ($page) => $page->where('branch', $cebu)
        ->where('branches', fn ($b) => collect($b)->pluck('id')->all() === [$cebu]));
    $this->get('/admin/pos/sales?branch='.otherBranch()->id)->assertInertia(fn ($page) => $page->where('branch', $cebu));
    $this->get('/admin/hr?branch='.otherBranch()->id)->assertInertia(fn ($page) => $page->where('branch', $cebu));

    // And they cannot ring up a sale at another branch.
    $this->post('/admin/pos', [
        'branch_id' => otherBranch()->id,
        'items' => [['kind' => 'service', 'id' => Treatment::where('slug', 'hydra-facial')->value('id'), 'quantity' => 1]],
        'discount_type' => 'none', 'method' => 'cash',
    ])->assertSessionHasErrors('branch_id');
});
