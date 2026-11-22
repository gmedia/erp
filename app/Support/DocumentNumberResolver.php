<?php

namespace App\Support;

use App\Models\ApPayment;
use App\Models\ArReceipt;
use App\Models\Asset;
use App\Models\CreditNote;
use App\Models\CustomerInvoice;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\SupplierBill;
use App\Models\VehicleLoan;

/**
 * Resolves a human-readable document number for polymorphic references
 * (pipeline/approval audit trails) instead of showing raw database ids.
 */
class DocumentNumberResolver
{
    private const ATTRIBUTES = [
        PurchaseRequest::class => 'pr_number',
        PurchaseOrder::class => 'po_number',
        GoodsReceipt::class => 'gr_number',
        SupplierBill::class => 'bill_number',
        ApPayment::class => 'payment_number',
        CustomerInvoice::class => 'invoice_number',
        ArReceipt::class => 'receipt_number',
        CreditNote::class => 'credit_note_number',
        StockTransfer::class => 'transfer_number',
        StockAdjustment::class => 'adjustment_number',
        Asset::class => 'asset_code',
        VehicleLoan::class => 'code',
    ];

    /**
     * @var array<string, array<string, string|null>>
     */
    private array $resolved = [];

    public function resolve(string|int|null $type, string|int|null $id): ?string
    {
        if (! $type || $id === null || $id === '') {
            return null;
        }

        $attribute = self::ATTRIBUTES[$type] ?? null;

        if ($attribute === null || ! class_exists($type)) {
            return null;
        }

        $key = (string) $id;

        if (array_key_exists($key, $this->resolved[$type] ?? [])) {
            return $this->resolved[$type][$key];
        }

        $value = $type::query()->whereKey($id)->value($attribute);

        $this->resolved[$type][$key] = is_string($value) ? $value : null;

        return $this->resolved[$type][$key];
    }
}
