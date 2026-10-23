<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Payroll from the time record: holidays, tardiness, absences and premiums,
// each kept as its own payslip line so a person can see why their pay moved.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('name', 120);
            $table->string('type', 16); // regular or special
            $table->timestamps();
            $table->unique(['organization_id', 'date']);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->unsignedInteger('overtime_minutes')->default(0)->after('overtime');
            $table->decimal('holiday_pay', 12, 2)->default(0)->after('overtime_minutes');
            $table->unsignedInteger('late_minutes')->default(0)->after('unpaid_deduction');
            $table->decimal('tardiness_deduction', 12, 2)->default(0)->after('late_minutes');
            $table->decimal('absent_days', 5, 2)->default(0)->after('tardiness_deduction');
            $table->decimal('absence_deduction', 12, 2)->default(0)->after('absent_days');
        });

        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->decimal('overtime_multiplier', 4, 2)->default(1.25);
            $table->decimal('regular_holiday_multiplier', 4, 2)->default(2.00);
            $table->decimal('special_holiday_multiplier', 4, 2)->default(1.30);
            $table->unsignedSmallInteger('late_grace_minutes')->default(5);
        });
    }

    public function down(): void
    {
        Schema::table('payroll_settings', fn (Blueprint $table) => $table->dropColumn(['overtime_multiplier', 'regular_holiday_multiplier', 'special_holiday_multiplier', 'late_grace_minutes']));
        Schema::table('payslips', fn (Blueprint $table) => $table->dropColumn(['overtime_minutes', 'holiday_pay', 'late_minutes', 'tardiness_deduction', 'absent_days', 'absence_deduction']));
        Schema::dropIfExists('holidays');
    }
};
