<?php

namespace App\Http\Resources\CustomerInvoices;

use App\Http\Resources\Concerns\BuildsProductUnitItemResourceData;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CustomerInvoice $resource
 */
class CustomerInvoiceResource extends JsonResource
{
    use BuildsProductUnitItemResourceData;

    public function toArray($request): array
    {
        /** @var Customer|null $customer */
        $customer = $this->resource->customer;
        /** @var Branch|null $branch */
        $branch = $this->resource->branch;
        /** @var FiscalYear|null $fiscalYear */
        $fiscalYear = $this->resource->fiscalYear;
        /** @var User|null $creator */
        $creator = $this->resource->creator;
        /** @var User|null $sender */
        $sender = $this->resource->sender;

        $items = [];

        if ($this->resource->relationLoaded('items')) {
            /** @var EloquentCollection<int, CustomerInvoiceItem> $customerInvoiceItems */
            $customerInvoiceItems = $this->resource->items;

            $items = $customerInvoiceItems
                ->map(fn (CustomerInvoiceItem $item) => $this->productUnitItemResourceData($item, [
                    'account_id' => $item->account_id,
                    'account_name' => $item->account->name,
                    'description' => $item->description,
                    'quantity' => (string) $item->quantity,
                    'unit_price' => (string) $item->unit_price,
                    'discount_percent' => (string) $item->discount_percent,
                    'tax_percent' => (string) $item->tax_percent,
                    'line_total' => (string) $item->line_total,
                    'notes' => $item->notes,
                ]))
                ->values()
                ->all();
        }

        return [
            'id' => $this->resource->id,
            'invoice_number' => $this->resource->invoice_number,
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
            'invoice_date' => $this->resource->invoice_date->toDateString(),
            'due_date' => $this->resource->due_date->toDateString(),
            'payment_terms' => $this->resource->payment_terms,
            'currency' => $this->resource->currency,
            'subtotal' => (string) $this->resource->subtotal,
            'tax_amount' => (string) $this->resource->tax_amount,
            'discount_amount' => (string) $this->resource->discount_amount,
            'grand_total' => (string) $this->resource->grand_total,
            'amount_received' => (string) $this->resource->amount_received,
            'credit_note_amount' => (string) $this->resource->credit_note_amount,
            'amount_due' => (string) $this->resource->amount_due,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'journal_entry_id' => $this->resource->journal_entry_id,
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $creator?->name,
            ] : null,
            'sent_by' => $this->resource->sent_by ? [
                'id' => $this->resource->sent_by,
                'name' => $sender?->name,
            ] : null,
            'sent_at' => $this->resource->sent_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
            'items' => $items,
        ];
    }
}
