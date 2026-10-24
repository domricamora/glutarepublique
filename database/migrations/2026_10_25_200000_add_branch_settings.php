<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Settings each branch keeps for itself (owner, 2026-10-24). Every one is
// optional: an empty value falls back to the clinic-wide setting, so nothing
// changes for a branch until the office sets it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Off: the site still lists the branch, but clients book by phone; the desk can still book.
            $table->boolean('online_booking')->default(true)->after('is_active');
            $table->string('receipt_name', 120)->nullable()->after('map_url');
            $table->string('receipt_tin', 40)->nullable()->after('receipt_name');
            $table->string('receipt_address', 255)->nullable()->after('receipt_tin');
            $table->string('receipt_footer', 255)->nullable()->after('receipt_address');
            // Percent a discount may reach before the owner approves; null uses the clinic's limit.
            $table->decimal('discount_limit', 5, 2)->nullable()->after('receipt_footer');
            // Units at or below which stock counts as low, for products without their own level.
            $table->unsignedInteger('low_stock_level')->nullable()->after('discount_limit');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['online_booking', 'receipt_name', 'receipt_tin', 'receipt_address', 'receipt_footer', 'discount_limit', 'low_stock_level']);
        });
    }
};
