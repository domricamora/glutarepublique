<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Gluta RepubliQ Aesthetics Database Seeder.
 */
class DatabaseSeeder extends Seeder
{
    /** Named after the mall or building; `city` is the town or province (owner's location format, 2026-10-24). */
    public const BRANCHES = [
        ['name' => 'Robinsons Galleria Cebu II', 'slug' => 'cebu', 'city' => 'Cebu City', 'address' => '2nd Floor, General Maxilom Avenue corner Sergio Osmeña Boulevard, Brgy. Tejero', 'phone' => '+63 995 510 9095'],
        ['name' => 'Esperanza Building', 'slug' => 'san-pedro', 'city' => 'San Pedro, Laguna', 'address' => 'Ground Floor, 39 Pacita Ave.', 'phone' => '+63 995 510 9095'],
        ['name' => 'Carvajal Commercial Building 1', 'slug' => 'sta-rosa', 'city' => 'Sta. Rosa, Laguna', 'address' => '2nd Floor, Balibago', 'phone' => '+63 995 510 9095', 'is_active' => false],
        ['name' => 'Southwoods Mall', 'slug' => 'southwoods', 'city' => 'Biñan City, Laguna', 'address' => 'Third Floor, Commerce Avenue and Southwoods Avenue, Southwoods City, Barangay San Francisco', 'phone' => '+63 995 510 9095', 'opens_on' => '2027-01-01'],
        ['name' => 'SOMO A Vista Mall', 'slug' => 'bacoor', 'city' => 'Bacoor, Cavite', 'address' => 'Molino - Paliparan Rd, Molino IV', 'phone' => '+63 995 510 9095'],
    ];

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $organization = Organization::updateOrCreate(
            ['slug' => config('clinic.organization', 'glutarepubliq')],
            ['name' => config('clinic.name', 'Gluta RepubliQ Aesthetics')],
        );

        foreach (self::BRANCHES as $branch) {
            Branch::withoutGlobalScopes()->firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => $branch['slug']],
                $branch + ['email' => $branch['slug'].'@glutarepubliq.com'],
            );
        }

        $users = [
            ['admin@glutarepubliq.com', 'Admin Gluta Republiq', null, 'Super Admin'],
            ['owner@glutarepubliq.com', 'Karla Ugalino Velasco', $organization->id, 'Organization Owner'],
            ['reception@glutarepubliq.com', 'Receptionist', $organization->id, 'Receptionist'],
        ];

        foreach ($users as [$email, $name, $organizationId, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'organization_id' => $organizationId,
                'email_verified_at' => now(),
                // A new demo login gets the password from CLINIC_DEMO_PASSWORD (.env); without one it is unguessable.
            ] + ($user->exists ? [] : ['password' => config('clinic.demo_password') ?: Str::random(32)]))->save();
            $user->syncRoles($role);
        }

        $this->call(SiteContentSeeder::class);
        $this->call(MarketingContentSeeder::class);
        $this->call(PosDemoSeeder::class);
        $this->call(HrDemoSeeder::class);
        $this->call(HolidaySeeder::class);
        $this->call(AccountingSeeder::class);

        // No made-up activity: the owner runs this installation on live data
        // (2026-10-24). ShowcaseHistorySeeder, CrmDemoSeeder and
        // TrafficDemoSeeder stay for a demonstration install, run by hand.
    }
}
