<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the nightly stock check found: a product at a branch whose shelf count,
 * lots and stock ledger do not agree. Closed by the next check that finds
 * them in agreement again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_discrepancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->integer('on_hand');
            $table->integer('lots');
            $table->integer('ledger');
            $table->timestamp('found_at');
            $table->timestamp('checked_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_discrepancies');
    }
};
