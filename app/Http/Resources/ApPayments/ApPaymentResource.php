<?php

namespace App\Http\Resources\ApPayments;

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ApPayment $resource
 */
class ApPaymentResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Supplier|null $supplier */
        $supplier = $this->resource->supplier;
        /** @var Branch|null $branch */
        $branch = $this->resource->branch;
        /** @var FiscalYear|null $fiscalYear */
        $fiscalYear = $this->resource->fiscalYear;
        /** @var Account $bankAccount */
        $bankAccount = $this->resource->bankAccount;
        /** @var User|null $approver */
        $approver = $this->resource->approver;
        /** @var User|null $creator */
        $creator = $this->resource->creator;
        /** @var User|null $confirmer */
        $confirmer = $this->resource->confirmer;

        $allocations = [];

        if ($this->resource->relationLoaded('allocations')) {
            /** @var EloquentCollection<int, ApPaymentAllocation> $paymentAllocations */
            $paymentAllocations = $this->resource->allocations;

            $allocations = $paymentAllocations
                ->map(fn (ApPaymentAllocation $allocation) => [
                    'id' => $allocation->id,
                    'supplier_bill_id' => $allocation->supplier_bill_id,
                    'bill_number' => $allocation->supplierBill->bill_number,
                    'allocated_amount' => (string) $allocation->allocated_amount,
                    'discount_taken' => (string) $allocation->discount_taken,
                    'notes' => $allocation->notes,
                ])
                ->values()
                ->all();
        }

        return [
            'id' => $this->resource->id,
            'payment_number' => $this->resource->payment_number,
            'supplier' => [
                'id' => $this->resource->supplier_id,
                'name' => $supplier?->name,
            ],
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $branch?->name,
            ],
            'fiscal_year' => [
                'id' => $this->resource->fiscal_year_id,
                'name' => $fiscalYear?->name,
            ],
            'payment_date' => $this->resource->payment_date->toDateString(),
            'payment_method' => $this->resource->payment_method,
            'bank_account' => [
                'id' => $this->resource->bank_account_id,
                'name' => $bankAccount->name,
            ],
            'currency' => $this->resource->currency,
            'total_amount' => (string) $this->resource->total_amount,
            'total_allocated' => (string) $this->resource->total_allocated,
            'total_unallocated' => (string) $this->resource->total_unallocated,
            'reference' => $this->resource->reference,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'journal_entry_id' => $this->resource->journal_entry_id,
            'approved_by' => $this->resource->approved_by ? [
                'id' => $this->resource->approved_by,
                'name' => $approver?->name,
            ] : null,
            'approved_at' => $this->resource->approved_at?->toIso8601String(),
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $creator?->name,
            ] : null,
            'confirmed_by' => $this->resource->confirmed_by ? [
                'id' => $this->resource->confirmed_by,
                'name' => $confirmer?->name,
            ] : null,
            'confirmed_at' => $this->resource->confirmed_at?->toIso8601String(),
            'allocations' => $allocations,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
