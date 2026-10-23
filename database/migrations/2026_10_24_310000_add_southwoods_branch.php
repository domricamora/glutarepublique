<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A branch can be announced before it opens: until opens_on it shows on the
// website as "Opening <month>" and takes no bookings. Southwoods opens January 2027.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->date('opens_on')->nullable()->after('is_active');
        });

        foreach (DB::table('organizations')->pluck('id') as $org) {
            if (DB::table('branches')->where(['organization_id' => $org, 'slug' => 'southwoods'])->exists()) {
                continue;
            }
            DB::table('branches')->insert([
                'organization_id' => $org,
                'name' => 'Southwoods',
                'slug' => 'southwoods',
                'address' => 'Third Floor, Southwoods Mall, Commerce Avenue and Southwoods Avenue, Southwoods City, Barangay San Francisco',
                'city' => 'Biñan City, Laguna',
                'phone' => '+63 995 510 9095',
                'email' => 'southwoods@glutarepubliq.com',
                'image' => '/media/photos/clinic/southwoods-storefront.jpg',
                'hours' => json_encode(Branch::DEFAULT_HOURS),
                'is_active' => true,
                'opens_on' => '2027-01-01',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('branches')->where('slug', 'southwoods')->delete();
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn('opens_on'));
    }
};
