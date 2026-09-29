<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('allowances_total', 12, 2)->default(0)->after('overtime_amount');
            $table->decimal('component_deductions', 12, 2)->default(0)->after('late_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['allowances_total', 'component_deductions']);
        });
    }
};
