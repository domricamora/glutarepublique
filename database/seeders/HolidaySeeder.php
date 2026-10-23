<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\Organization;
use Illuminate\Database\Seeder;

/**
 * The 2026 Philippine holiday calendar as a starting point. Eid'l Fitr and
 * Eid'l Adha are left out: their dates are set by proclamation each year, so
 * HR adds them in Payroll > Deductions once announced. A date already on the
 * calendar is left as the office set it.
 */
class HolidaySeeder extends Seeder
{
    /** [date, name, type] */
    private const HOLIDAYS = [
        ['2026-01-01', "New Year's Day", 'regular'],
        ['2026-02-17', 'Chinese New Year', 'special'],
        ['2026-02-25', 'EDSA People Power Revolution Anniversary', 'special'],
        ['2026-04-02', 'Maundy Thursday', 'regular'],
        ['2026-04-03', 'Good Friday', 'regular'],
        ['2026-04-04', 'Black Saturday', 'special'],
        ['2026-04-09', 'Araw ng Kagitingan', 'regular'],
        ['2026-05-01', 'Labor Day', 'regular'],
        ['2026-06-12', 'Independence Day', 'regular'],
        ['2026-08-21', 'Ninoy Aquino Day', 'special'],
        ['2026-08-31', 'National Heroes Day', 'regular'],
        ['2026-11-01', "All Saints' Day", 'special'],
        ['2026-11-02', "All Souls' Day", 'special'],
        ['2026-11-30', 'Bonifacio Day', 'regular'],
        ['2026-12-08', 'Feast of the Immaculate Conception', 'special'],
        ['2026-12-24', 'Christmas Eve', 'special'],
        ['2026-12-25', 'Christmas Day', 'regular'],
        ['2026-12-30', 'Rizal Day', 'regular'],
        ['2026-12-31', 'Last Day of the Year', 'special'],
    ];

    public function run(): void
    {
        $org = Organization::where('slug', config('clinic.organization'))->firstOrFail()->id;

        foreach (self::HOLIDAYS as [$date, $name, $type]) {
            Holiday::withoutGlobalScopes()->firstOrCreate(
                ['organization_id' => $org, 'date' => $date],
                ['name' => $name, 'type' => $type],
            );
        }
    }
}
