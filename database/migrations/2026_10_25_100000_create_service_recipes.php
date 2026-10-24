<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A service's recipe (2026-10-24): the staff who may perform it, and the products one session uses, taken off the
// branch's shelf when the service is rung up. Each sale line also keeps what it
// cost on the day, so a later price change on a product never rewrites profit.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Whole units, like every count on the shelves.
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['treatment_id', 'product_id']);
        });

        // Who may perform a service. No rows means anyone at the branch can, so a
        // service works before the office has assigned anybody.
        Schema::create('specialist_treatment', function (Blueprint $table) {
            $table->foreignId('specialist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('treatment_id')->constrained()->cascadeOnDelete();
            $table->primary(['specialist_id', 'treatment_id']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            // Cost of goods for the line: the product's cost, or the service's recipe.
            $table->decimal('cost', 10, 2)->nullable()->after('line_total');
            // Recipe products the branch's records did not have enough of: [{product_id, name, needed, taken}].
            $table->json('stock_shortfall')->nullable()->after('cost');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['cost', 'stock_shortfall']);
        });
        Schema::dropIfExists('specialist_treatment');
        Schema::dropIfExists('treatment_products');
    }
};
