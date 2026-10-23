<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The DTR kiosk and the work schedule: who is meant to work when, a PIN to
// clock in at the branch tablet, and the photo taken as they do.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('work_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable(); // earlier than starts_at = ends the next morning
            $table->boolean('is_rest_day')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
            $table->index(['organization_id', 'work_date']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('kiosk_pin')->nullable(); // hashed
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('source', 16)->default('office')->after('overtime_minutes'); // office or kiosk
            $table->string('photo_in')->nullable()->after('source'); // private storage path
        });
    }

    public function down(): void
    {
        Schema::table('attendances', fn (Blueprint $table) => $table->dropColumn(['source', 'photo_in']));
        Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('kiosk_pin'));
        Schema::dropIfExists('work_schedules');
    }
};
