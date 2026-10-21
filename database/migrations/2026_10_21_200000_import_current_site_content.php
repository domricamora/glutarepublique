<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Content taken from the clinic's current site (glutarepubliq.com, 2026-10-21):
// the per-area and per-package prices each service publishes, and the exact
// branch addresses. Only the first-seeded guesses are replaced, so an address
// already corrected in the office is left alone.
return new class extends Migration
{
    private const ADDRESSES = [
        'cebu' => ['Robinsons Galleria Cebu II, General Maxilom Ave Ext', '2nd Floor, Robinsons Galleria, General Maxilom Ave corner Sergio Osmeña Blvd, Brgy. Tejero, Cebu City'],
        'san-pedro' => ['National Highway, San Pedro, Laguna', 'Ground Floor, Esperanza Bldg., 39 Pacita Ave, San Pedro, Laguna'],
        'sta-rosa' => ['Tagaytay Rd, Sta. Rosa, Laguna', '2nd Floor, Carvajal Commercial Bldg. 1, Balibago, Sta. Rosa, Laguna'],
        'bacoor' => ['SOMO A Vista Mall, Molino Blvd, Bacoor, Cavite', 'SOMO A Vista Mall, Daang Hari Vista City corner Molino, Bacoor, Cavite'],
    ];

    public function up(): void
    {
        Schema::table('treatments', function (Blueprint $table) {
            $table->json('price_list')->nullable()->after('promo_price');
        });

        foreach (self::ADDRESSES as $slug => [$old, $new]) {
            DB::table('branches')->where('slug', $slug)->where('address', $old)->update(['address' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::ADDRESSES as $slug => [$old, $new]) {
            DB::table('branches')->where('slug', $slug)->where('address', $new)->update(['address' => $old]);
        }

        Schema::table('treatments', function (Blueprint $table) {
            $table->dropColumn('price_list');
        });
    }
};
