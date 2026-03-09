<?php

namespace Database\Seeders;

use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApprovalDelegationSampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $financeDirector = User::where('email', 'director.finance@dokfin.id')->first();
        $hrManager = User::where('email', 'manager.hr@dokfin.id')->first();
        $itStaff = User::where('email', 'staff.it@dokfin.id')->first();

        // 1. Finance Director delegates to HR Manager (Active now)
        ApprovalDelegation::create([
            'delegator_user_id' => $financeDirector->id,
            'delegate_user_id' => $hrManager->id,
            'approvable_type' => null, // All types
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(5),
            'reason' => 'Annual Leave',
            'is_active' => true,
        ]);

        // 2. HR Manager delegates to IT Staff (Future)
        ApprovalDelegation::create([
            'delegator_user_id' => $hrManager->id,
            'delegate_user_id' => $itStaff->id,
            'approvable_type' => 'App\Models\PurchaseRequest',
            'start_date' => now()->addMonths(1),
            'end_date' => now()->addMonths(1)->addDays(7),
            'reason' => 'Overseas Duty',
            'is_active' => true,
        ]);
    }
}
