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
        $financeDirector = User::where('email', 'director.finance@example.com')->first();
        $hrManager = User::where('email', 'manager.hr@example.com')->first();
        $itStaff = User::where('email', 'staff.it@example.com')->first();

        // 1. Finance Director delegates to HR Manager (Active now)
        ApprovalDelegation::create([
            'delegator_user_id' => $financeDirector->id,
            'delegate_user_id' => $hrManager->id,
            'approvable_type' => null, // All types
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(5),
            'reason' => 'Cuti tahunan',
            'is_active' => true,
        ]);

        // 2. HR Manager delegates to IT Staff (Future)
        ApprovalDelegation::create([
            'delegator_user_id' => $hrManager->id,
            'delegate_user_id' => $itStaff->id,
            'approvable_type' => 'App\Models\PurchaseRequest',
            'start_date' => now()->addMonths(1),
            'end_date' => now()->addMonths(1)->addDays(7),
            'reason' => 'Tugas dinas luar negeri',
            'is_active' => true,
        ]);
    }
}
