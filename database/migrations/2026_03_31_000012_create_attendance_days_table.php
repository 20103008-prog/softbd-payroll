<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_days', function (Blueprint $table) {
            $table->id();
            $table->date('work_date');
            $table->string('employee_code');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();

            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->string('status', 32)->default('unknown');

            $table->timestamps();

            $table->unique(['work_date', 'employee_code'], 'attendance_days_work_date_employee_code_unique');
            $table->index(['work_date']);
            $table->index(['employee_id', 'work_date']);
            $table->index(['shift_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_days');
    }
};

