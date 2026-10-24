<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A recipe shortfall is cleared once someone has counted the shelf: who and
 * when, so the alert list only shows what still needs a count.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->timestamp('shortfall_counted_at')->nullable()->after('stock_shortfall');
            $table->foreignId('shortfall_counted_by')->nullable()->after('shortfall_counted_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shortfall_counted_by');
            $table->dropColumn('shortfall_counted_at');
        });
    }
};
