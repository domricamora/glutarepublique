<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Two journal posts stated things the clinic does not offer (2026-10-24): a
// chemical peel, and a 1,500 peso consultation fee credited within 30 days.
// The seeder only creates posts once, so the published text is corrected here.
return new class extends Migration
{
    private const REPLACE = [
        ['A chemical peel goes further, using controlled exfoliation to refresh tone and texture, and usually needs three to six sessions spaced a few weeks apart.', 'A Diamond Peel or an Aqua Sponge Peel goes further, exfoliating to refresh tone and texture, and works best as a series spaced a few weeks apart.'],
        ['A Hydra Facial is the gentlest starting point', 'A Hydrafacial is the gentlest starting point'],
        ['a Hydra Facial suits most skin', 'a Hydrafacial suits most skin'],
        ["\n\nThe consultation fee is 1,500 pesos, and on a first visit it is credited against your first treatment booked within 30 days.", ''],
    ];

    public function up(): void
    {
        foreach (self::REPLACE as [$old, $new]) {
            foreach (['body', 'takeaways'] as $column) {
                DB::table('posts')->where($column, 'like', '%'.addcslashes($old, '%_\\').'%')
                    ->update([$column => DB::raw('REPLACE('.$column.', '.DB::getPdo()->quote($old).', '.DB::getPdo()->quote($new).')')]);
            }
        }
    }

    public function down(): void
    {
        // The wrong statements are not put back.
    }
};
