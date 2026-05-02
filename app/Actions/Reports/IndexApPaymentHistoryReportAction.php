<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Models\ApPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexApPaymentHistoryReportAction
{
    use HandlesReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $query = ApPayment::query()
            ->from('ap_payments as ap')
            ->join('suppliers as s', 'ap.supplier_id', '=', 's.id')
            ->join('branches as b', 'ap.branch_id', '=', 'b.id')
            ->leftJoin('accounts as ba', 'ap.bank_account_id', '=', 'ba.id')
            ->selectRaw(implode(",\n                ", [
                'ap.id',
                'ap.payment_number',
                'ap.payment_date',
                'ap.payment_method',
                'ap.currency',
                'ap.total_amount',
                'ap.total_allocated',
                'ap.total_unallocated',
                'ap.reference',
                'ap.status',
                's.id as supplier_id',
                's.name as supplier_name',
                'b.id as branch_id',
                'b.name as branch_name',
                'ba.id as bank_account_id',
                'ba.name as bank_account_name',
            ]))
            ->whereIn('ap.status', ['confirmed', 'reconciled'])
            ->withCasts([
                'total_amount' => 'decimal:2',
                'total_allocated' => 'decimal:2',
                'total_unallocated' => 'decimal:2',
                'payment_date' => 'date',
            ]);

        $this->applyIntegerFilter($request, $query, 'supplier_id', 'ap.supplier_id');
        $this->applyIntegerFilter($request, $query, 'branch_id', 'ap.branch_id');
        $this->applyStringFilter($request, $query, 'payment_method', 'ap.payment_method');
        $this->applyStringFilter($request, $query, 'status', 'ap.status');
        $this->applyDateRangeFilter($request, $query, 'ap.payment_date', 'start_date', 'end_date');
        $this->applySearchFilter($request, $query, ['ap.payment_number', 's.name', 'b.name', 'ba.name', 'ap.reference']);

        $sortBy = $request->string('sort_by', 'payment_date')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();
        $sortMap = [
            'supplier_name' => 's.name',
            'branch_name' => 'b.name',
            'bank_account_name' => 'ba.name',
            'payment_date' => 'ap.payment_date',
            'payment_number' => 'ap.payment_number',
            'payment_method' => 'ap.payment_method',
            'total_amount' => 'ap.total_amount',
            'total_allocated' => 'ap.total_allocated',
        ];
        $resolvedSort = $sortMap[$sortBy] ?? 'ap.' . $sortBy;
        $query->orderBy($resolvedSort, $sortDirection);

        return $this->exportOrPaginate($request, $query);
    }
}
