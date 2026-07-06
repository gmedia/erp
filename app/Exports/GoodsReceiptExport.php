<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\GoodsReceipt;
use Illuminate\Database\Eloquent\Builder;

class GoodsReceiptExport extends BaseExport
{
    public function query(): Builder
    {
        $query = GoodsReceipt::query()->with(['purchaseOrder.supplier', 'warehouse', 'receiver']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['gr_number', 'supplier_delivery_note', 'notes'],
            [
                'purchase_order' => 'purchase_order_id',
                'warehouse' => 'warehouse_id',
                'status' => 'status',
                'received_by' => 'received_by',
            ],
            [
                'receipt_date' => ['from' => 'receipt_date_from', 'to' => 'receipt_date_to'],
            ],
            ['gr_number', 'receipt_date', 'status', 'created_at'],
        );

        return $query;
    }

    protected function columns(): array
    {
        return [
            'ID' => fn (GoodsReceipt $gr): mixed => $gr->id,
            'GR Number' => fn (GoodsReceipt $gr): mixed => $gr->gr_number,
            'PO Number' => fn (GoodsReceipt $gr): mixed => $this->relatedAttribute($gr, 'purchaseOrder', 'po_number'),
            'Supplier' => fn (GoodsReceipt $gr): mixed => $gr->purchaseOrder->getRelationValue('supplier')?->name,
            'Warehouse' => fn (GoodsReceipt $gr): mixed => $this->relatedAttribute($gr, 'warehouse', 'name'),
            'Receipt Date' => fn (GoodsReceipt $gr): mixed => $this->formatDateValue($gr->receipt_date, 'Y-m-d'),
            'Supplier Delivery Note' => fn (GoodsReceipt $gr): mixed => $gr->supplier_delivery_note,
            'Status' => fn (GoodsReceipt $gr): mixed => $gr->status,
            'Received By' => fn (GoodsReceipt $gr): mixed => $this->relatedAttribute($gr, 'receiver', 'name'),
            'Notes' => fn (GoodsReceipt $gr): mixed => $gr->notes,
            'Confirmed At' => fn (GoodsReceipt $gr): mixed => $this->formatIso8601($gr->confirmed_at),
            'Created At' => fn (GoodsReceipt $gr): mixed => $this->formatIso8601($gr->created_at),
        ];
    }
}
