<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The client's onboarding sheet (2026-10-23): three branches with their exact
// addresses, Sta. Rosa no longer listed (kept, hidden, so its history stays),
// the owner's name, and the clinic's current Facebook page.
return new class extends Migration
{
    private const BRANCHES = [
        'cebu' => ['2nd Floor, Robinsons Galleria, General Maxilom Avenue corner Sergio Osmeña Boulevard, Brgy. Tejero', 'Cebu City'],
        'san-pedro' => ['Ground Floor, Esperanza Bldg., 39 Pacita Ave.', 'San Pedro, Laguna'],
        'bacoor' => ['SOMO A Vista Mall, Molino - Paliparan Rd, Molino IV', 'Bacoor, Cavite'],
    ];

    public function up(): void
    {
        foreach (self::BRANCHES as $slug => [$address, $city]) {
            DB::table('branches')->where('slug', $slug)->update(['address' => $address, 'city' => $city, 'is_active' => true]);
        }
        DB::table('branches')->where('slug', 'sta-rosa')->update(['is_active' => false]);

        DB::table('users')->where('email', 'owner@glutarepubliq.com')->update(['name' => 'Karla Ugalino Velasco']);
        $owner = DB::table('users')->where('email', 'owner@glutarepubliq.com')->value('id');
        if ($owner) {
            DB::table('employees')->where('user_id', $owner)->update(['name' => 'Karla Ugalino Velasco']);
        }

        foreach (DB::table('organizations')->pluck('id') as $org) {
            DB::table('clinic_settings')->updateOrInsert(
                ['organization_id' => $org, 'key' => 'social.facebook'],
                ['value' => 'profile.php?id=61584019485763', 'updated_at' => now(), 'created_at' => now()],
            );
        }

        // The seeded privacy text named four cities; a page already reworded in the office is left alone.
        foreach (DB::table('pages')->get(['id', 'sections']) as $page) {
            $sections = (string) $page->sections;
            if (str_contains($sections, 'Cebu, San Pedro, Sta. Rosa and Bacoor')) {
                DB::table('pages')->where('id', $page->id)->update(['sections' => str_replace('Cebu, San Pedro, Sta. Rosa and Bacoor', 'Cebu, San Pedro and Bacoor', $sections)]);
            }
        }
    }

    public function down(): void
    {
        DB::table('branches')->where('slug', 'sta-rosa')->update(['is_active' => true]);
    }
};
