<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The owner asked for each visit's IP address and rough location.
// Location is filled in afterwards by `traffic:locate`, never during the page request.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('visitor');
            $table->string('country', 80)->nullable()->after('ip_address');
            $table->string('region', 80)->nullable()->after('country');
            $table->string('city', 80)->nullable()->after('region');
            $table->timestamp('located_at')->nullable()->after('city');
            $table->index(['located_at', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex(['located_at', 'ip_address']);
            $table->dropColumn(['ip_address', 'country', 'region', 'city', 'located_at']);
        });
    }
};
