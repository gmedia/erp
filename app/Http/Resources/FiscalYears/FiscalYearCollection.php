<?php

namespace App\Http\Resources\FiscalYears;

use App\Actions\FiscalYears\GetPreferredFiscalYearAction;
use App\Http\Resources\SimpleCrudCollection;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;

class FiscalYearCollection extends SimpleCrudCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = FiscalYearResource::class;

    /**
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        /** @var Builder<FiscalYear> $query */
        $query = FiscalYear::query()->orderBy('start_date', 'desc');

        $status = $request->input('status');

        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        /** @var EloquentCollection<int, FiscalYear> $fiscalYears */
        $fiscalYears = $query->get();

        $preferred = app(GetPreferredFiscalYearAction::class)->execute($fiscalYears);

        return [
            'meta' => [
                'preferred_fiscal_year_id' => $preferred?->id,
            ],
        ];
    }
}
