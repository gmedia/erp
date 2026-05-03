<?php

namespace App\Http\Resources\ArReceipts;

use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArReceipt $resource
 */
class ArReceiptResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Customer|null $customer */
        $customer = $this->resource->customer;
        /** @var Branch|null $branch */
        $branch = $this->resource->branch;
        /** @var FiscalYear|null $fiscalYear */
        $fiscalYear = $this->resource->fiscalYear;
        /** @var Account|null $bankAccount */
        $bankAccount = $this->resource->bankAccount;
        /** @var User|null $creator */
        $creator = $this->resource->creator;
        /** @var User|null $confirmer */
        $confirmer = $this->resource->confirmer;

        $allocations = [];

        if ($this->resource->relationLoaded('allocations')) {
            /** @var EloquentCollection<int, ArReceiptAllocation> $arReceiptAllocations */
            $arReceiptAllocations = $this->resource->allocations;

            $allocations = $arReceiptAllocations
                ->map(fn (ArReceiptAllocation $allocation) => [
                    'id' => $allocation->id,
                    'customer_invoice_id' => $allocation->customer_invoice_id,
                    'invoice_number' => $allocation->customerInvoice->invoice_number,
                    'allocated_amount' => (string) $allocation->allocated_amount,
                    'discount_given' => (string) $allocation->discount_given,
                    'notes' => $allocation->notes,
                ])
                ->values()
                ->all();
        }

        return [
            'id' => $this->resource->id,
            'receipt_number' => $this->resource->receipt_number,
            'customer' => [
                'id' => $this->resource->customer_id,
                'name' => $customer?->name,
            ],
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $branch?->name,
            ],
            'fiscal_year' => [
                'id' => $this->resource->fiscal_year_id,
                'name' => $fiscalYear?->name,
            ],
            'receipt_date' => $this->resource->receipt_date->toDateString(),
            'payment_method' => $this->resource->payment_method,
            'bank_account' => [
                'id' => $this->resource->bank_account_id,
                'name' => $bankAccount?->name,
            ],
            'currency' => $this->resource->currency,
            'total_amount' => (string) $this->resource->total_amount,
            'total_allocated' => (string) $this->resource->total_allocated,
            'total_unallocated' => (string) $this->resource->total_unallocated,
            'reference' => $this->resource->reference,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'journal_entry_id' => $this->resource->journal_entry_id,
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $creator?->name,
            ] : null,
            'confirmed_by' => $this->resource->confirmed_by ? [
                'id' => $this->resource->confirmed_by,
                'name' => $confirmer?->name,
            ] : null,
            'confirmed_at' => $this->resource->confirmed_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
            'allocations' => $allocations,
        ];
    }
}
