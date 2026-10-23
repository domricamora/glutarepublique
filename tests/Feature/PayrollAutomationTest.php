<?php

use App\Actions\Payroll\CalculatePayslip;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

/**
 * One week, Monday 2025-03-03 to Friday 2025-03-07, shifts 09:00 to 18:00.
 * 22,000 a month = 1,000 a day = 125 an hour.
 *   Mon  in 09:20          -> 20 minutes late
 *   Tue  no clock-in       -> absent
 *   Wed  regular holiday, worked -> +100% of a day
 *   Thu  out 20:00         -> 2 hours overtime at 125%
 *   Fri  approved leave    -> not an absence
 */
it('works out tardiness, absences, holiday pay and overtime from the time record', function () {
    $person = Employee::active()->orderBy('id')->firstOrFail();
    $person->forceFill(['base_salary' => 22000, 'monthly_allowance' => 0, 'pay_schedule' => 'monthly'])->save();
    $monday = CarbonImmutable::parse('2025-03-03');

    foreach (range(0, 4) as $i) {
        WorkSchedule::create(['employee_id' => $person->id, 'work_date' => $monday->addDays($i)->toDateString(), 'is_rest_day' => false, 'starts_at' => '09:00', 'ends_at' => '18:00']);
    }
    Holiday::create(['date' => '2025-03-05', 'name' => 'Test holiday', 'type' => 'regular']);
    $day = fn (int $i, string $in, string $out, int $ot = 0) => Attendance::create([
        'employee_id' => $person->id, 'work_date' => $monday->addDays($i)->toDateString(),
        'time_in' => $in, 'time_out' => $out, 'minutes' => (strtotime($out) - strtotime($in)) / 60, 'overtime_minutes' => $ot,
    ]);
    $day(0, '09:20', '18:00');
    $day(2, '09:00', '18:00');
    $day(3, '09:00', '20:00', 120);
    LeaveRequest::create(['employee_id' => $person->id, 'type' => 'annual', 'from_date' => '2025-03-07', 'to_date' => '2025-03-07', 'days' => 1, 'status' => 'approved', 'reason' => 'Test']);

    $slip = app(CalculatePayslip::class)($person, $monday, $monday->addDays(4));

    expect($slip['late_minutes'])->toBe(20)
        ->and($slip['tardiness_deduction'])->toBe(41.67)
        ->and($slip['absent_days'])->toBe(1.0)
        ->and($slip['absence_deduction'])->toBe(1000.0)
        ->and($slip['holiday_pay'])->toBe(1000.0)
        ->and($slip['overtime_minutes'])->toBe(120)
        ->and($slip['overtime'])->toBe(312.5)
        ->and($slip['total_deductions'])->toBeGreaterThan(1041.67);
});

it('never calls a holiday or a rest day an absence', function () {
    $person = Employee::active()->orderBy('id')->firstOrFail();
    $monday = CarbonImmutable::parse('2025-03-10');
    WorkSchedule::create(['employee_id' => $person->id, 'work_date' => '2025-03-10', 'is_rest_day' => false, 'starts_at' => '09:00', 'ends_at' => '18:00']);
    WorkSchedule::create(['employee_id' => $person->id, 'work_date' => '2025-03-11', 'is_rest_day' => true]);
    Holiday::create(['date' => '2025-03-10', 'name' => 'Test holiday', 'type' => 'special']);

    $slip = app(CalculatePayslip::class)($person, $monday, $monday->addDay());

    expect($slip['absent_days'])->toBe(0.0)->and($slip['absence_deduction'])->toBe(0.0);
});
