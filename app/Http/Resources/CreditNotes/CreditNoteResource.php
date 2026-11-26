<?php

namespace App\Http\Resources\CreditNotes;

use App\Http\Resources\Concerns\BuildsAuditStampResourceData;
use App\Models\Branch;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\Department;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CreditNote $resource
 */
class CreditNoteResource extends JsonResource
{
    use BuildsAuditStampResourceData;

    public function toArray($request): array
    {
        /** @var Customer|null $customer */
        $customer = $this->resource->customer;
        /** @var CustomerInvoice|null $customerInvoice */
        $customerInvoice = $this->resource->customerInvoice;
        /** @var Branch|null $branch */
        $branch = $this->resource->branch;
        /** @var Department|null $department */
        $department = $this->resource->department;
        /** @var FiscalYear|null $fiscalYear */
        $fiscalYear = $this->resource->fiscalYear;

        $items = [];

        if ($this->resource->relationLoaded('items')) {
            /** @var EloquentCollection<int, CreditNoteItem> $creditNoteItems */
            $creditNoteItems = $this->resource->items;

            $items = $creditNoteItems
                ->map(fn (CreditNoteItem $item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product?->name,
                    'account_id' => $item->account_id,
                    'account_name' => $item->account->name,
                    'description' => $item->description,
                    'quantity' => (string) $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                    'tax_percent' => (string) $item->tax_percent,
                    'line_total' => (string) $item->line_total,
                    'notes' => $item->notes,
                ])
                ->values()
                ->all();
        }

        return [
            'id' => $this->resource->id,
            'credit_note_number' => $this->resource->credit_note_number,
            'customer' => [
                'id' => $this->resource->customer_id,
                'name' => $customer?->name,
            ],
            'customer_invoice' => $this->resource->customer_invoice_id ? [
                'id' => $this->resource->customer_invoice_id,
                'invoice_number' => $customerInvoice?->invoice_number,
            ] : null,
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $branch?->name,
            ],
            'department' => [
                'id' => $this->resource->department_id,
                'name' => $department?->name,
            ],
            'fiscal_year' => [
                'id' => $this->resource->fiscal_year_id,
                'name' => $fiscalYear?->name,
            ],
            'credit_note_date' => $this->resource->credit_note_date->toDateString(),
            'reason' => $this->resource->reason,
            'subtotal' => (string) $this->resource->subtotal,
            'tax_amount' => (string) $this->resource->tax_amount,
            'grand_total' => (string) $this->resource->grand_total,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'journal_entry_id' => $this->resource->journal_entry_id,
            ...$this->auditStampFields($this->resource),
            'items' => $items,
        ];
    }
}
