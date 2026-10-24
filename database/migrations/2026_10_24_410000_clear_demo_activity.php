<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

// The owner is moving the installation to live data (2026-10-24): the made-up
// activity the demo seeders wrote goes. Setup stays: branches, staff roster,
// logins, chart of accounts, periods, holidays, menu, offers, pages, FAQs,
// testimonials, journal posts and the audit log.
//
// Leads, bookings, chats and sales are written to storage/app/archive first,
// in case a real enquiry or a test sale is among them. Website traffic is not
// archived: it is counts, not records anyone needs back.
return new class extends Migration
{
    private const ARCHIVE = [
        'leads', 'crm_activities', 'appointments', 'chat_conversations', 'chat_messages',
        'sales', 'sale_items', 'payments',
    ];

    private const CLEAR = [
        'sale_items', 'payments', 'sales', 'journal_lines', 'journal_entries',
        'crm_activities', 'appointments', 'chat_messages', 'chat_conversations', 'leads',
        'page_views', 'attendances', 'leave_requests', 'work_schedules',
        'payslips', 'payroll_runs', 'approvals',
        'inventory_movements', 'product_batches', 'product_stocks', 'suppliers',
    ];

    public function up(): void
    {
        $tables = array_values(array_filter(self::CLEAR, fn (string $t) => Schema::hasTable($t)));

        if (collect($tables)->contains(fn (string $t) => DB::table($t)->exists())) {
            $archive = collect(self::ARCHIVE)->filter(fn (string $t) => Schema::hasTable($t))
                ->mapWithKeys(fn (string $t) => [$t => DB::table($t)->get()]);
            File::ensureDirectoryExists(storage_path('app/archive'));
            File::put(storage_path('app/archive/activity-before-2026-10-24.json'), $archive->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        // Every table that points at these is in the list, so the checks are
        // only off for the length of the deletes.
        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                DB::table($table)->delete();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // Demo activity is not restored; leads, bookings, chats and sales are in storage/app/archive.
    }
};
