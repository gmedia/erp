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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_id')->nullable()->after('id');
            $table->decimal('salary', 10, 2)->nullable()->change();
            $table->string('employment_status')->default('regular')->after('hire_date');
            $table->date('termination_date')->nullable()->after('employment_status');
        });

        // Set default employee_id for existing records
        $employees = \Illuminate\Support\Facades\DB::table('employees')->get();
        foreach ($employees as $emp) {
            \Illuminate\Support\Facades\DB::table('employees')
                ->where('id', $emp->id)
                ->update(['employee_id' => 'EMP-' . str_pad($emp->id, 5, '0', STR_PAD_LEFT)]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_id')->nullable(false)->change();
            $table->unique('employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['employee_id', 'employment_status', 'termination_date']);
            $table->decimal('salary', 10, 2)->nullable(false)->change();
        });
    }
};
