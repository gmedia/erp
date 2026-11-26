<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Exports\Concerns\ExportsNumberFormats;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class PurchaseOrderExport extends BaseExport implements WithColumnFormatting
{
    use ExportsNumberFormats;

    protected array $defaultSortColumns = ['order_date', 'po_number'];

    public function query(): Builder
    {
        $query = PurchaseOrder::query()->with(['supplier', 'warehouse']);

        $statuses = array_filter((array) ($this->filters['status'] ?? []));

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['po_number', 'payment_terms', 'notes', 'shipping_address'],
            [
                'supplier' => 'supplier_id',
                'warehouse' => 'warehouse_id',
                'currency' => 'currency',
            ],
            [
                'order_date' => ['from' => 'order_date_from', 'to' => 'order_date_to'],
            ],
            [
                'po_number',
                'order_date',
                'expected_delivery_date',
                'currency',
                'status',
                'grand_total',
                'created_at',
            ]
        );

        return $query;
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (PurchaseOrder $po): mixed => $po->id,
            'PO Number' => fn (PurchaseOrder $po): mixed => $po->po_number,
            'Supplier' => fn (PurchaseOrder $po): mixed => $this->relatedAttribute($po, 'supplier', 'name'),
            'Warehouse' => fn (PurchaseOrder $po): mixed => $this->relatedAttribute($po, 'warehouse', 'name'),
            'Order Date' => fn (PurchaseOrder $po): mixed => $this->formatDateValue($po->order_date, 'Y-m-d'),
            'Expected Delivery Date' => fn (PurchaseOrder $po): mixed => $this->formatDateValue(
                $po->expected_delivery_date,
                'Y-m-d',
            ),
            'Payment Terms' => fn (PurchaseOrder $po): mixed => $po->payment_terms,
            'Currency' => fn (PurchaseOrder $po): mixed => $po->currency,
            'Status' => fn (PurchaseOrder $po): mixed => $po->status,
            'Subtotal' => fn (PurchaseOrder $po): mixed => $this->numericValue($po->subtotal),
            'Tax Amount' => fn (PurchaseOrder $po): mixed => $this->numericValue($po->tax_amount),
            'Discount Amount' => fn (PurchaseOrder $po): mixed => $this->numericValue($po->discount_amount),
            'Grand Total' => fn (PurchaseOrder $po): mixed => $this->numericValue($po->grand_total),
            'Notes' => fn (PurchaseOrder $po): mixed => $po->notes,
            'Created At' => fn (PurchaseOrder $po): mixed => $this->formatIso8601($po->created_at),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return ['Subtotal', 'Tax Amount', 'Discount Amount', 'Grand Total'];
    }
}
