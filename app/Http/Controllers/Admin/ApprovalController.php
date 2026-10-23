<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Approvals\DecideApproval;
use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The owner's inbox: refunds, discounts over the limit, payroll runs and leave
 * waiting for her decision, and the history of what was decided. Staff see
 * the same screen with only their own requests, so they know where theirs is.
 */
class ApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $owner = $request->user()->can('approvals.decide');
        $scope = fn () => Approval::with(['requester:id,name', 'decider:id,name'])
            ->when(! $owner, fn ($q) => $q->where('requested_by', $request->user()->id));

        $row = fn (Approval $a) => [
            'id' => $a->id,
            'type' => $a->type,
            'type_label' => Approval::TYPES[$a->type] ?? $a->type,
            'status' => $a->status,
            'summary' => $a->summary,
            'amount' => $a->amount,
            'requested_by' => $a->requester?->name,
            'requested_at' => $a->created_at?->toIso8601String(),
            'decided_by' => $a->decider?->name,
            'decided_at' => $a->decided_at?->toIso8601String(),
            'decision_note' => $a->decision_note,
            'outcome' => $a->outcome,
        ];

        return Inertia::render('admin/approvals/index', [
            'owner' => $owner,
            'pending' => $scope()->pending()->oldest()->get()->map($row),
            'decided' => $scope()->where('status', '!=', 'pending')->latest('decided_at')->limit(40)->get()->map($row),
            // Anyone on the staff roll can file their own leave from here.
            'canFileLeave' => Employee::active()->where('user_id', $request->user()->id)->exists(),
            'leaveTypes' => LeaveRequest::TYPES,
        ]);
    }

    public function decide(Request $request, Approval $approval, DecideApproval $decide): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:255', 'required_if:decision,reject'],
        ], ['note.required_if' => 'Say why, so the person who asked knows.']);

        $decide($approval, $data['decision'] === 'approve', $data['note'] ?? null, $request->user());

        return back()->with('success', ($data['decision'] === 'approve' ? 'Approved. ' : 'Rejected. ').($approval->fresh()->outcome ?? ''));
    }
}
