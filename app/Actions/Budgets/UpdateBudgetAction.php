<?php

namespace App\Actions\Budgets;

use App\Models\Budget;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateBudgetAction
{
    public function execute(Budget $budget, array $data): Budget
    {
        if ($budget->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => ['Only draft budgets can be updated.'],
            ]);
        }

        $lines = $data['lines'] ?? null;
        unset($data['lines']);

        if ($lines !== null) {
            $data['total_amount'] = collect($lines)->sum(fn (array $line): float => (float) $line['allocated_amount']);
        }

        return DB::transaction(function () use ($budget, $data, $lines): Budget {
            $budget->update($data);

            if ($lines !== null) {
                $this->syncLines($budget, $lines);
            }

            return $budget->load('lines.account');
        });
    }

    private function syncLines(Budget $budget, array $lines): void
    {
        $existingIds = [];

        foreach ($lines as $line) {
            if (isset($line['id'])) {
                $budget->lines()->where('id', $line['id'])->update($line);
                $existingIds[] = $line['id'];
            } else {
                $budget->lines()->create($line);
            }
        }

        $budget->lines()->whereNotIn('id', $existingIds)->delete();
    }
}
