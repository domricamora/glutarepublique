<?php

namespace App\Actions\Hr;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\PayrollSetting;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;

/**
 * What the time record says about a person over a period: minutes late,
 * days absent and holidays worked. The time record screen and payroll both
 * read it, so the deduction on a payslip is always the lateness HR saw.
 *
 * Only scheduled days count: without a shift there is nothing to be late for
 * or absent from. Approved leave and holidays are never absences, and today
 * is not judged until it is over.
 */
class TimeRecord
{
    public static function grace(): int
    {
        return (int) (PayrollSetting::current()->late_grace_minutes ?? 5);
    }

    /** Minutes late past the grace period, or 0. */
    public static function lateMinutes(?string $timeIn, ?WorkSchedule $shift, int $grace): int
    {
        if (! $timeIn || ! $shift || $shift->is_rest_day || ! $shift->starts_at) {
            return 0;
        }

        $late = intdiv((int) strtotime($timeIn) - (int) strtotime($shift->starts_at), 60);

        return $late > $grace ? $late : 0;
    }

    /**
     * @return array{late_minutes: int, absent_days: int, regular_holidays_worked: int, special_holidays_worked: int}
     */
    public function period(Employee $employee, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $grace = self::grace();
        $range = [$from->toDateString(), $to->toDateString()];

        $shifts = WorkSchedule::where('employee_id', $employee->id)->whereBetween('work_date', $range)->get()
            ->keyBy(fn (WorkSchedule $s) => $s->work_date->toDateString());
        $days = Attendance::where('employee_id', $employee->id)->whereBetween('work_date', $range)->whereNotNull('time_in')->get()
            ->keyBy(fn (Attendance $a) => CarbonImmutable::parse($a->work_date)->toDateString());
        $holidays = Holiday::whereBetween('date', $range)->get()->keyBy(fn (Holiday $h) => $h->date->toDateString());
        $leave = LeaveRequest::where('employee_id', $employee->id)->approved()
            ->whereDate('from_date', '<=', $range[1])->whereDate('to_date', '>=', $range[0])->get();

        $result = ['late_minutes' => 0, 'absent_days' => 0, 'regular_holidays_worked' => 0, 'special_holidays_worked' => 0];

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $date = $day->toDateString();
            $shift = $shifts->get($date);
            $worked = $days->get($date);
            $holiday = $holidays->get($date);

            if ($worked && $holiday) {
                $result[$holiday->type === 'regular' ? 'regular_holidays_worked' : 'special_holidays_worked']++;
            }

            if ($worked) {
                $result['late_minutes'] += self::lateMinutes($worked->time_in, $shift, $grace);

                continue;
            }

            $onLeave = $leave->contains(fn (LeaveRequest $l) => $l->from_date->toDateString() <= $date && $l->to_date->toDateString() >= $date);
            if ($shift && ! $shift->is_rest_day && ! $holiday && ! $onLeave && $day->lt(CarbonImmutable::today())) {
                $result['absent_days']++;
            }
        }

        return $result;
    }
}
