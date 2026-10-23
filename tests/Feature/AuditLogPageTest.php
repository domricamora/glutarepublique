<?php

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('shows the audit log with who did what', function () {
    $owner = User::where('email', 'owner@glutarepubliq.com')->firstOrFail();
    $this->actingAs($owner);
    AuditLog::record('settings.branch.update', 'Updated the Cebu branch');

    $this->get('/admin/audit')->assertOk()->assertSee('Updated the Cebu branch');
});
