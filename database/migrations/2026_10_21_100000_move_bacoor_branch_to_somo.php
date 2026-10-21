<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The Bacoor clinic is in SOMO A Vista Mall (per the branch's Facebook page),
// not on Aguinaldo Highway as first seeded. Only the seeded value is replaced,
// so an address already corrected in the office is left alone.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('branches')
            ->where('slug', 'bacoor')
            ->where('address', 'Aguinaldo Highway, Bacoor, Cavite')
            ->update(['address' => 'SOMO A Vista Mall, Molino Blvd, Bacoor, Cavite']);
    }

    public function down(): void
    {
        DB::table('branches')
            ->where('slug', 'bacoor')
            ->where('address', 'SOMO A Vista Mall, Molino Blvd, Bacoor, Cavite')
            ->update(['address' => 'Aguinaldo Highway, Bacoor, Cavite']);
    }
};
