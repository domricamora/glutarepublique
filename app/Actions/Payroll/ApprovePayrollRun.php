<?php

namespace App\Actions\Payroll;

use App\Actions\Accounting\PostPayroll;
use App\Models\Approval;
use App\Models\AuditLog;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Approves a draft run and posts it to the books: the gate before real money
 * leaves, so only the owner holds it. Approving from the run's own page or
 * from the approvals inbox both come through here, and either way the
 * waiting request is closed.
 */
class ApprovePayrollRun
{
    public function __construct(private readonly PostPayroll $post) {}

    public function __invoke(PayrollRun $run, User $owner): void
    {
        if ($run->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Only a draft run can be approved.']);
        }

        ($this->post)($run, $owner);
        $run->update(['status' => 'approved', 'approved_at' => now(), 'posted_at' => now()]);

        AuditLog::record('payroll.approve', $run->label.' approved and posted to the books.', $run, [
            'gross' => $run->gross,
            'deductions' => $run->total_deductions,
            'net' => $run->net,
            'payslips' => $run->payslips()->count(),
        ]);
    }

    /** Closes the inbox request when the owner approved straight from the run page. */
    public static function closeRequest(PayrollRun $run, User $owner): void
    {
        Approval::pending()->where('type', 'payroll')->where('subject_type', PayrollRun::class)->where('subject_id', $run->id)
            ->update(['status' => 'approved', 'decided_by' => $owner->id, 'decided_at' => now(), 'outcome' => $run->label.' approved from the run page.']);
    }
}
