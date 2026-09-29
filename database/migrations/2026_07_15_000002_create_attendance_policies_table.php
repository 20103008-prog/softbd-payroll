<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_policies', function (Blueprint $table) {
            $table->id();
            $table->time('work_start_time')->default('09:00:00');
            $table->unsignedSmallInteger('grace_minutes')->default(0)->comment('Minutes after start still counted as on-time');
            $table->unsignedSmallInteger('half_day_after_minutes')->nullable()->comment('Late by more than this = half day (optional)');
            $table->unsignedSmallInteger('EndTime')->nullable()->comment('end time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
