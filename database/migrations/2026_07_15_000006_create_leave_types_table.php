<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('annual_quota')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->timestamps();
        });

        DB::table('leave_types')->insert([
            ['name' => 'Annual Leave', 'annual_quota' => 20, 'is_paid' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sick Leave', 'annual_quota' => 14, 'is_paid' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Casual Leave', 'annual_quota' => 10, 'is_paid' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Unpaid Leave', 'annual_quota' => 0, 'is_paid' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
