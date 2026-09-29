<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Weekly-off days as an array of ISO-ish weekday numbers (0=Sun … 6=Sat).
            // NULL means "use the app default"; an empty array means "no weekend".
            $table->json('weekend_days')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('weekend_days');
        });
    }
};
