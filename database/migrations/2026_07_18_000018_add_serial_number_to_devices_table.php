<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('serial_number', 64)->nullable()->after('name')->unique();
            $table->timestamp('last_seen_at')->nullable()->after('is_active');
        });

        // Allow multiple ADMS devices without conflicting on a shared placeholder IP.
        Schema::table('devices', function (Blueprint $table) {
            $table->dropUnique(['ip_address', 'port']);
            $table->index(['ip_address', 'port']);
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['ip_address', 'port']);
            $table->unique(['ip_address', 'port']);
            $table->dropColumn(['serial_number', 'last_seen_at']);
        });
    }
};
