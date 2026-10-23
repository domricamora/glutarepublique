<?php

namespace App\Actions\Hr;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkSchedule;

/**
 * One punch of the time clock: the first of the day clocks in, the next
 * clocks out. Shared by the office button and the branch kiosk, so a day's
 * hours and overtime are worked out the same way wherever someone punched.
 */
class ClockPunch
{
    /** A day with no schedule counts overtime past eight hours. */
    public const STANDARD_MINUTES = 480;

    public function __invoke(Employee $employee, string $day, string $source = 'office', ?string $photo = null): Attendance
    {
        $now = now()->format('H:i');
        $attendance = Attendance::firstOrNew(['employee_id' => $employee->id, 'work_date' => $day]);
        $clockingIn = $attendance->time_in === null;

        $attendance->fill([
            'time_in' => $attendance->time_in ?? $now,
            'time_out' => $clockingIn ? null : $now,
        ]);
        if ($clockingIn) {
            $attendance->fill(['source' => $source, 'photo_in' => $photo]);
        }

        $attendance->minutes = $attendance->time_in && $attendance->time_out
            ? (int) max(0, (strtotime($attendance->time_out) - strtotime($attendance->time_in)) / 60)
            : 0;

        $planned = WorkSchedule::where('employee_id', $employee->id)->whereDate('work_date', $day)->first()?->minutes();
        $attendance->overtime_minutes = (int) max(0, $attendance->minutes - ($planned ?: self::STANDARD_MINUTES));
        $attendance->save();

        return $attendance;
    }
}
