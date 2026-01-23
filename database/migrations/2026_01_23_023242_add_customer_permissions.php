<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create customer parent permission
        $customer = Permission::create([
            'name' => 'customer',
            'display_name' => 'Customer',
            'parent_id' => null,
        ]);

        // Create customer child permissions
        Permission::create([
            'name' => 'customer.create',
            'display_name' => 'Create Customer',
            'parent_id' => $customer->id,
        ]);

        Permission::create([
            'name' => 'customer.edit',
            'display_name' => 'Edit Customer',
            'parent_id' => $customer->id,
        ]);

        Permission::create([
            'name' => 'customer.delete',
            'display_name' => 'Delete Customer',
            'parent_id' => $customer->id,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::where('name', 'like', 'customer%')->delete();
    }
};
