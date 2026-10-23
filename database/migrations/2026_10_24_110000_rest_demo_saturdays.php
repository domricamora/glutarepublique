<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The first demo rota scheduled Saturdays while the demo clock-ins stop on
// Friday, so payroll would read every Saturday as an absence. Only seeded rows
// (no created_by) change; a Saturday planned in the office is left alone.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('work_schedules')
            ->whereNull('created_by')
            ->whereRaw('DAYOFWEEK(work_date) = 7')
            ->update(['is_rest_day' => true, 'starts_at' => null, 'ends_at' => null]);
    }

    public function down(): void
    {
        // A data correction; nothing to restore.
    }
};
