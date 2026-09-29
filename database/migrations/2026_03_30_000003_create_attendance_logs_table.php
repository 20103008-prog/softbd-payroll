<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->comment('As reported by device; join to employees.employee_code for profile');
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->dateTime('punched_at')->comment('Device punch time (event timestamp)');
            $table->string('status', 32)->comment('e.g. in, out');
            $table->timestamps();

            $table->index(['employee_code', 'punched_at']);
            $table->index(['device_id', 'punched_at']);
            $table->index(['punched_at']);
            $table->index(['status', 'punched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
