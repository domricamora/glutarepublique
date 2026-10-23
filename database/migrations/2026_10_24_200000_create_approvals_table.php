<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Requests that only the owner may grant: refunds, discounts over the limit,
// payroll runs and leave. The row is the audit trail of who asked, who
// decided, when, and what happened as a result.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24); // refund, discount, payroll, leave
            $table->string('status', 16)->default('pending'); // pending, approved, rejected
            $table->nullableMorphs('subject');
            $table->string('summary', 255);
            $table->decimal('amount', 12, 2)->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 255)->nullable();
            $table->string('outcome', 255)->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
