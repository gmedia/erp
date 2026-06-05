<?php

namespace App\Actions\Budgets;

use App\Models\Budget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreBudgetAction
{
    public function execute(array $data): Budget
    {
        $lines = $data['lines'];
        unset($data['lines']);

        $data['ulid'] = Str::ulid()->toBase32();
        $data['created_by'] = auth()->id();
        $data['status'] = 'draft';
        $data['total_amount'] = collect($lines)->sum(fn (array $line): float => (float) $line['allocated_amount']);

        return DB::transaction(function () use ($data, $lines): Budget {
            $budget = Budget::create($data);

            foreach ($lines as $line) {
                $budget->lines()->create($line);
            }

            return $budget->load('lines.account');
        });
    }
}
