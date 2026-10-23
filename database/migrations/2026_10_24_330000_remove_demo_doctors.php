<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// The owner asked for every made-up member of staff to go. The three demo
// doctors' specialist rows become real clinicians (appointments must name a
// specialist, so the rows are renamed rather than deleted); their staff
// records go, and their bylines on journal posts become the clinic's.
return new class extends Migration
{
    private const RENAME = [
        'dr-camille-rivera' => ['ivy-neri', 'Ivy Neri'],
        'dr-adrian-santos' => ['jermaine-ramirez', 'Jermaine Ramirez'],
        'dr-rhea-kapoor' => ['janet-culango', 'Janet Culango'],
    ];

    private const DOCTORS = ['Dr. Camille Rivera', 'Dr. Adrian Santos', 'Dr. Rhea Kapoor'];

    public function up(): void
    {
        foreach (self::RENAME as $old => [$slug, $name]) {
            foreach (DB::table('specialists')->where('slug', $old)->get(['id', 'organization_id']) as $row) {
                if (DB::table('specialists')->where(['organization_id' => $row->organization_id, 'slug' => $slug])->exists()) {
                    continue;
                }
                DB::table('specialists')->where('id', $row->id)->update([
                    'slug' => $slug, 'name' => $name, 'photo' => null, 'credentials' => null, 'focus' => json_encode([]),
                ]);
            }
        }

        $doctors = DB::table('employees')->whereIn('name', self::DOCTORS)->pluck('id');
        DB::table('payslips')->whereIn('employee_id', $doctors)->delete();
        DB::table('employees')->whereIn('id', $doctors)->delete();

        DB::table('posts')->whereIn('author_name', self::DOCTORS)->update(['author_name' => 'Gluta RepubliQ clinical team']);
    }

    public function down(): void
    {
        // Made-up people are not restored.
    }
};
