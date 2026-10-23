<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Website traffic: one row per public page view. No IP address is stored;
// the visitor column is a hash that changes every day (see TrackPageView).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('path', 255);
            $table->string('source', 80)->default('direct');
            $table->string('medium', 80)->nullable();
            $table->string('campaign', 120)->nullable();
            $table->string('referrer_host', 120)->nullable();
            $table->string('device', 16);
            $table->char('visitor', 16);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
