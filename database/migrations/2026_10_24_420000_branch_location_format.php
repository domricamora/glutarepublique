<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The owner's location format (2026-10-24): city or province, then the mall,
// then the detailed address, then the phone. A branch is now named after its
// mall (or building), the town lives in `city`, and the street address no
// longer repeats the mall. Only values still as seeded change, so a branch
// the office has edited is left alone.
return new class extends Migration
{
    /** slug => [field => [seeded value, new value]] */
    private const CHANGES = [
        'cebu' => [
            'address' => ['2nd Floor, Robinsons Galleria, General Maxilom Avenue corner Sergio Osmeña Boulevard, Brgy. Tejero', '2nd Floor, General Maxilom Avenue corner Sergio Osmeña Boulevard, Brgy. Tejero'],
        ],
        'san-pedro' => [
            'name' => ['San Pedro', 'Esperanza Building'],
            'address' => ['Ground Floor, Esperanza Bldg., 39 Pacita Ave.', 'Ground Floor, 39 Pacita Ave.'],
        ],
        'sta-rosa' => [
            'name' => ['Sta. Rosa', 'Carvajal Commercial Building 1'],
            'city' => ['Sta. Rosa', 'Sta. Rosa, Laguna'],
            'address' => ['2nd Floor, Carvajal Commercial Bldg. 1, Balibago, Sta. Rosa, Laguna', '2nd Floor, Balibago'],
        ],
        'southwoods' => [
            'name' => ['Southwoods', 'Southwoods Mall'],
            'address' => ['Third Floor, Southwoods Mall, Commerce Avenue and Southwoods Avenue, Southwoods City, Barangay San Francisco', 'Third Floor, Commerce Avenue and Southwoods Avenue, Southwoods City, Barangay San Francisco'],
        ],
        'bacoor' => [
            'name' => ['Bacoor', 'SOMO A Vista Mall'],
            'address' => ['SOMO A Vista Mall, Molino - Paliparan Rd, Molino IV', 'Molino - Paliparan Rd, Molino IV'],
        ],
    ];

    public function up(): void
    {
        $this->apply(0, 1);
    }

    public function down(): void
    {
        $this->apply(1, 0);
    }

    private function apply(int $from, int $to): void
    {
        foreach (self::CHANGES as $slug => $fields) {
            foreach ($fields as $field => $values) {
                DB::table('branches')->where('slug', $slug)->where($field, $values[$from])->update([$field => $values[$to]]);
            }
        }
    }
};
