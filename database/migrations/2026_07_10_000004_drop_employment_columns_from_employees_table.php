<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('employees', 'department_id')) {
                // Drop the foreign key first if it exists
                try {
                    $table->dropForeign(['department_id']);
                } catch (Exception) {
                    // Foreign key may not exist
                }
                $columnsToDrop[] = 'department_id';
            }

            if (Schema::hasColumn('employees', 'position_id')) {
                try {
                    $table->dropForeign(['position_id']);
                } catch (Exception) {
                    // Foreign key may not exist
                }
                $columnsToDrop[] = 'position_id';
            }

            if (Schema::hasColumn('employees', 'branch_id')) {
                $columnsToDrop[] = 'branch_id';
            }

            if (Schema::hasColumn('employees', 'salary')) {
                $columnsToDrop[] = 'salary';
            }

            if (Schema::hasColumn('employees', 'hire_date')) {
                $columnsToDrop[] = 'hire_date';
            }

            if (Schema::hasColumn('employees', 'employment_status')) {
                $columnsToDrop[] = 'employment_status';
            }

            if (Schema::hasColumn('employees', 'termination_date')) {
                $columnsToDrop[] = 'termination_date';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('phone');
            $table->unsignedBigInteger('position_id')->nullable()->after('department_id');
            $table->unsignedBigInteger('branch_id')->nullable()->after('position_id');
            $table->decimal('salary', 10, 2)->nullable()->after('user_id');
            $table->date('hire_date')->after('salary');
            $table->string('employment_status')->default('regular')->after('hire_date');
            $table->date('termination_date')->nullable()->after('employment_status');
        });
    }
};
