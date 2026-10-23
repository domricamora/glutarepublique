<?php

namespace App\Actions\Accounting;

use App\Models\PayrollRun;
use App\Models\User;

/**
 * Puts an approved pay run into the books (plan.md §24): what the month cost as
 * an expense, what is owed to the government and still to the staff, and then
 * the money leaving the till when it is paid.
 */
class PostPayroll
{
    public function __construct(private readonly PostEntry $entries) {}

    public function __invoke(PayrollRun $run, User $user): void
    {
        if ($run->posted_at) {
            return;
        }

        $payslips = $run->payslips()->get();
        $gross = round((float) $payslips->sum('gross'), 2);
        $net = round((float) $payslips->sum('net'), 2);
        $sss = round((float) $payslips->sum('sss'), 2);
        $philhealth = round((float) $payslips->sum('philhealth'), 2);
        $pagibig = round((float) $payslips->sum('pagibig'), 2);
        $tax = round((float) $payslips->sum('withholding_tax'), 2);
        // Pay not earned (unpaid leave, lateness, absence) was never an expense,
        // so it comes off wages rather than being booked as a deduction.
        $notEarned = round((float) $payslips->sum('unpaid_deduction') + (float) $payslips->sum('tardiness_deduction') + (float) $payslips->sum('absence_deduction'), 2);
        // Loan and advance repayments reduce what staff owe the clinic.
        $other = round((float) $payslips->sum('other_deductions'), 2);

        // Dr wages earned = Cr contributions + Cr repayments + Cr net pay owed.
        $lines = [['account' => '5100', 'debit' => round($gross - $notEarned, 2), 'memo' => $run->label]];

        foreach ([['2100', $sss], ['2110', $philhealth], ['2120', $pagibig], ['2130', $tax], ['1200', $other]] as [$code, $amount]) {
            if ($amount > 0.01) {
                $lines[] = ['account' => $code, 'credit' => $amount, 'memo' => $code === '1200' ? 'Staff loans and advances repaid, '.$run->label : $run->label];
            }
        }

        $lines[] = ['account' => '2200', 'credit' => $net, 'memo' => $run->label];

        ($this->entries)($lines, [
            'entry_date' => $run->paid_on->toDateString(),
            'memo' => 'Payroll '.$run->label,
            'source_type' => 'payroll',
            'source_id' => $run->id,
        ], $user);
    }

    /** The money actually leaving the till on payday. */
    public function paid(PayrollRun $run, User $user): void
    {
        $net = round((float) $run->payslips()->sum('net'), 2);

        if ($net < 0.01) {
            return;
        }

        ($this->entries)([
            ['account' => '2200', 'debit' => $net, 'memo' => $run->label],
            ['account' => '1000', 'credit' => $net, 'memo' => 'Net pay, '.$run->label],
        ], [
            'entry_date' => $run->paid_on->toDateString(),
            'memo' => 'Net pay disbursed '.$run->label,
            'source_type' => 'payroll',
            'source_id' => $run->id.'-paid',
        ], $user);
    }
}
