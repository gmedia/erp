<?php

use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CoaVersion::query()->each(function (CoaVersion $coaVersion): void {
            $exists = Account::query()
                ->where('coa_version_id', $coaVersion->id)
                ->where('code', '1999-IBC')
                ->exists();

            if ($exists) {
                return;
            }

            $parent = Account::query()
                ->where('coa_version_id', $coaVersion->id)
                ->where('code', '11000')
                ->first();

            Account::create([
                'coa_version_id' => $coaVersion->id,
                'parent_id' => $parent?->id,
                'code' => '1999-IBC',
                'name' => 'Inter-Branch Clearing',
                'type' => 'asset',
                'sub_type' => 'current_asset',
                'normal_balance' => 'debit',
                'level' => $parent ? $parent->level + 1 : 3,
                'is_active' => true,
                'is_cash_flow' => false,
            ]);
        });
    }

    public function down(): void
    {
        Account::query()->where('code', '1999-IBC')->delete();
    }
};
