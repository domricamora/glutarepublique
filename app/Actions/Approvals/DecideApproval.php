<?php

namespace App\Actions\Approvals;

use App\Actions\Payroll\ApprovePayrollRun;
use App\Actions\Pos\RingUpSale;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The owner's decision on a request. Approving carries the action out as the
 * person who asked (their name stays on the sale or refund), inside the same
 * transaction as the decision: if the action fails, nothing is marked
 * approved and the request stays waiting.
 */
class DecideApproval
{
    public function __construct(
        private readonly RingUpSale $till,
        private readonly ApprovePayrollRun $approveRun,
    ) {}

    public function __invoke(Approval $approval, bool $approve, ?string $note, User $owner): Approval
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['approval' => 'This request has already been decided.']);
        }

        return DB::transaction(function () use ($approval, $approve, $note, $owner) {
            $outcome = $approve ? $this->carryOut($approval, $owner) : $this->turnDown($approval, $owner, $note);

            $approval->update([
                'status' => $approve ? 'approved' : 'rejected',
                'decided_by' => $owner->id,
                'decided_at' => now(),
                'decision_note' => $note,
                'outcome' => $outcome,
            ]);

            AuditLog::record(
                'approval.'.($approve ? 'approve' : 'reject'),
                ($approve ? 'Approved: ' : 'Rejected: ').$approval->summary.($note ? " ({$note})" : ''),
                $approval,
                ['type' => $approval->type, 'amount' => $approval->amount, 'outcome' => $outcome],
            );

            return $approval;
        });
    }

    private function carryOut(Approval $approval, User $owner): string
    {
        $payload = $approval->payload ?? [];
        $requester = $approval->requester;

        return match ($approval->type) {
            'refund' => $this->refund($approval, $payload, $requester),
            'discount' => 'Sale '.($this->till)($payload, $requester)->sale->reference.' rung up with the approved discount.',
            'payroll' => $this->payroll($approval, $owner),
            'leave' => $this->leave($approval, 'approved', $owner, null),
            default => throw ValidationException::withMessages(['approval' => 'Unknown request type.']),
        };
    }

    private function turnDown(Approval $approval, User $owner, ?string $note): string
    {
        if ($approval->type === 'leave') {
            $this->leave($approval, 'declined', $owner, $note);
        }

        return 'Nothing was changed.';
    }

    /** @param  array<string, mixed>  $payload */
    private function refund(Approval $approval, array $payload, User $requester): string
    {
        $sale = Sale::findOrFail($approval->subject_id);
        $this->till->refund($sale, (float) $payload['amount'], (string) $payload['method'], (string) $payload['reason'], $requester);

        AuditLog::record('sale.refund', sprintf('Refunded %s on %s (%s), approved by the owner', number_format((float) $payload['amount'], 2), $sale->reference, $payload['reason']), $sale, $payload);

        return 'Refund of '.number_format((float) $payload['amount'], 2).' recorded on '.$sale->reference.'.';
    }

    private function payroll(Approval $approval, User $owner): string
    {
        $run = PayrollRun::findOrFail($approval->subject_id);
        ($this->approveRun)($run, $owner);

        return $run->label.' approved and posted to the books.';
    }

    private function leave(Approval $approval, string $status, User $owner, ?string $note): string
    {
        $leave = LeaveRequest::findOrFail($approval->subject_id);
        $leave->update(['status' => $status, 'review_note' => $note, 'reviewed_by' => $owner->id, 'reviewed_at' => now()]);

        return 'Leave '.$status.'.';
    }
}
