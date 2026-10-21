<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Gluta RepubliQ Aesthetics Database Seeder.
 */
class DatabaseSeeder extends Seeder
{
    public const BRANCHES = [
        ['name' => 'Robinsons Galleria Cebu II', 'slug' => 'cebu', 'city' => 'Cebu City', 'address' => 'Robinsons Galleria Cebu II, General Maxilom Ave Ext', 'phone' => '+63 995 510 9095'],
        ['name' => 'San Pedro', 'slug' => 'san-pedro', 'city' => 'San Pedro', 'address' => 'National Highway, San Pedro, Laguna', 'phone' => '+63 995 510 9095'],
        ['name' => 'Sta. Rosa', 'slug' => 'sta-rosa', 'city' => 'Sta. Rosa', 'address' => 'Tagaytay Rd, Sta. Rosa, Laguna', 'phone' => '+63 995 510 9095'],
        ['name' => 'Bacoor', 'slug' => 'bacoor', 'city' => 'Bacoor', 'address' => 'SOMO A Vista Mall, Molino Blvd, Bacoor, Cavite', 'phone' => '+63 995 510 9095'],
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
            ['owner@glutarepubliq.com', 'Dr. Camille Rivera', $organization->id, 'Organization Owner'],
            ['reception@glutarepubliq.com', 'Receptionist', $organization->id, 'Receptionist'],
        ];

        foreach ($users as [$email, $name, $organizationId, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'organization_id' => $organizationId,
                'email_verified_at' => now(),
            ] + ($user->exists ? [] : ['password' => 'ThEsEcReT1126']))->save();
            $user->syncRoles($role);
        }

        $this->call(SiteContentSeeder::class);
        $this->call(MarketingContentSeeder::class);
        $this->call(PosDemoSeeder::class);
        $this->call(HrDemoSeeder::class);
        $this->call(AccountingSeeder::class);

        if (! app()->runningUnitTests()) {
            $this->call(CrmDemoSeeder::class);
        }
    }
}
