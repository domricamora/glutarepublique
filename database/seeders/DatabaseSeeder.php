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
    public const BRANCHES = [
        ['name' => 'Robinsons Galleria Cebu II', 'slug' => 'cebu', 'city' => 'Cebu City', 'address' => '2nd Floor, Robinsons Galleria, General Maxilom Avenue corner Sergio Osmeña Boulevard, Brgy. Tejero', 'phone' => '+63 995 510 9095'],
        ['name' => 'San Pedro', 'slug' => 'san-pedro', 'city' => 'San Pedro, Laguna', 'address' => 'Ground Floor, Esperanza Bldg., 39 Pacita Ave.', 'phone' => '+63 995 510 9095'],
        ['name' => 'Sta. Rosa', 'slug' => 'sta-rosa', 'city' => 'Sta. Rosa', 'address' => '2nd Floor, Carvajal Commercial Bldg. 1, Balibago, Sta. Rosa, Laguna', 'phone' => '+63 995 510 9095', 'is_active' => false],
        ['name' => 'Southwoods', 'slug' => 'southwoods', 'city' => 'Biñan City, Laguna', 'address' => 'Third Floor, Southwoods Mall, Commerce Avenue and Southwoods Avenue, Southwoods City, Barangay San Francisco', 'phone' => '+63 995 510 9095', 'opens_on' => '2027-01-01'],
        ['name' => 'Bacoor', 'slug' => 'bacoor', 'city' => 'Bacoor, Cavite', 'address' => 'SOMO A Vista Mall, Molino - Paliparan Rd, Molino IV', 'phone' => '+63 995 510 9095'],
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

        if (! app()->runningUnitTests()) {
            // A year of history first (once per database); the CRM seeder then
            // fills today and this week around it, checking the diary for each slot.
            $this->call(ShowcaseHistorySeeder::class);
            $this->call(CrmDemoSeeder::class);
            $this->call(TrafficDemoSeeder::class);
        }
    }
}
