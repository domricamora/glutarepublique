<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// The owner restarted the menu from the clinic's own collaterals (2026-10-24):
// every service, category, package, promotion, retail product and the
// made-up membership tiers go, and the
// seeders load the new menu straight after. The old rows are written to
// storage/app/archive first, so nothing is lost for good.
//
// Receipts already keep their own description, and leads, testimonials and
// promotions let go of a deleted service. A booking could not, so it now does
// the same: the booking stays in the diary, without the service it named.
return new class extends Migration
{
    private const ARCHIVE = [
        'treatment_categories', 'treatments', 'promotions', 'membership_tiers',
        'products', 'product_batches', 'product_stocks', 'inventory_movements',
    ];

    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['treatment_id']);
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('treatment_id')->nullable()->change();
            $table->foreign('treatment_id')->references('id')->on('treatments')->nullOnDelete();
            // What was booked, kept like a receipt line's description so the diary still reads.
            $table->string('service_name')->nullable()->after('treatment_id');
        });
        DB::table('appointments')
            ->join('treatments', 'treatments.id', '=', 'appointments.treatment_id')
            ->update(['appointments.service_name' => DB::raw('treatments.name')]);

        Schema::table('treatments', function (Blueprint $table) {
            $table->string('subcategory', 80)->nullable()->after('treatment_category_id');
        });

        if (DB::table('treatments')->exists() || DB::table('products')->exists() || DB::table('promotions')->exists() || DB::table('membership_tiers')->exists()) {
            $archive = collect(self::ARCHIVE)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->get()]);
            File::ensureDirectoryExists(storage_path('app/archive'));
            File::put(storage_path('app/archive/catalog-before-2026-10-24.json'), $archive->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Children go with their parents: batches, stock and movements with the
        // product, treatments with their category.
        DB::table('promotions')->delete();
        // Made-up monthly plans; the clinic offers no membership yet.
        DB::table('membership_tiers')->delete();
        DB::table('products')->delete();
        DB::table('treatments')->delete();
        DB::table('treatment_categories')->delete();
    }

    public function down(): void
    {
        // The old menu is in storage/app/archive; it is not put back automatically.
        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn('subcategory');
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('service_name');
        });
    }
};
