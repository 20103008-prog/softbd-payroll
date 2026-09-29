<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('department')->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
            $table->string('email')->nullable()->after('designation_id');
            $table->string('phone', 50)->nullable()->after('email');
            $table->date('joining_date')->nullable()->after('phone');
            $table->string('status')->default('active')->after('joining_date')->index();
            $table->string('address', 500)->nullable()->after('status');
        });

        // Link existing employees to the departments seeded from their text value.
        $departments = DB::table('departments')->pluck('id', 'name');
        foreach ($departments as $name => $id) {
            DB::table('employees')->where('department', $name)->update(['department_id' => $id]);
        }

        // Drop the legacy free-text column now that data lives in department_id.
        // SQLite requires dropping the index before the column.
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['department']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('department')->nullable()->after('employee_code');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('designation_id');
            $table->dropColumn(['email', 'phone', 'joining_date', 'status', 'address']);
        });
    }
};
