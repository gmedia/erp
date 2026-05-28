<?php

namespace App\Actions\Concerns;

use App\Models\ApPayment;
use App\Models\ArReceipt;
use App\Models\CreditNote;
use App\Models\CustomerInvoice;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SupplierBill;
use App\Models\SupplierReturn;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class TransactionMappedIndexConfigurations
{
    /**
     * @var array<string, array{
     *     model_class: class-string<Model>,
     *     with: array<int, string>,
     *     search_fields: array<int, string>,
     *     filter_keys: array<int, string>,
     *     default_sort_by: string,
     *     allowed_sorts: array<int, string>,
     *     sort_map: array<string, string>
     * }>
     */
    private const CONFIGURATIONS = [
        'customer_invoices' => [
            'model_class' => CustomerInvoice::class,
            'with' => [
                'customer',
                'branch',
                'fiscalYear',
                'creator',
                'sender',
                'items.product',
                'items.account',
                'items.unit',
            ],
            'search_fields' => [
                'invoice_number',
                'payment_terms',
                'notes',
            ],
            'filter_keys' => [
                'customer_id',
                'branch_id',
                'fiscal_year_id',
                'status',
                'currency',
                'invoice_date_from',
                'invoice_date_to',
                'due_date_from',
                'due_date_to',
                'grand_total_min',
                'grand_total_max',
                'amount_due_min',
                'amount_due_max',
            ],
            'default_sort_by' => 'created_at',
            'allowed_sorts' => [
                'id',
                'invoice_number',
                'customer_id',
                'branch_id',
                'invoice_date',
                'due_date',
                'currency',
                'status',
                'grand_total',
                'amount_due',
                'created_at',
                'updated_at',
            ],
            'sort_map' => [
                'customer' => 'customer_id',
                'branch' => 'branch_id',
            ],
        ],
        'ar_receipts' => [
            'model_class' => ArReceipt::class,
            'with' => [
                'customer',
                'branch',
                'fiscalYear',
                'bankAccount',
                'creator',
                'confirmer',
                'allocations.customerInvoice',
            ],
            'search_fields' => [
                'receipt_number',
                'reference',
                'notes',
            ],
            'filter_keys' => [
                'customer_id',
                'branch_id',
                'fiscal_year_id',
                'status',
                'currency',
                'receipt_date_from',
                'receipt_date_to',
                'grand_total_min',
                'grand_total_max',
            ],
            'default_sort_by' => 'created_at',
            'allowed_sorts' => [
                'id',
                'receipt_number',
                'customer_id',
                'branch_id',
                'receipt_date',
                'currency',
                'status',
                'total_amount',
                'total_unallocated',
                'grand_total',
                'created_at',
                'updated_at',
            ],
            'sort_map' => [
                'customer' => 'customer_id',
                'branch' => 'branch_id',
            ],
        ],
        'credit_notes' => [
            'model_class' => CreditNote::class,
            'with' => [
                'customer',
                'branch',
                'fiscalYear',
                'creator',
                'confirmer',
                'items.product',
            ],
            'search_fields' => [
                'credit_note_number',
                'notes',
            ],
            'filter_keys' => [
                'customer_id',
                'branch_id',
                'fiscal_year_id',
                'status',
                'currency',
                'credit_note_date_from',
                'credit_note_date_to',
                'grand_total_min',
                'grand_total_max',
            ],
            'default_sort_by' => 'created_at',
            'allowed_sorts' => [
                'id',
                'credit_note_number',
                'customer_id',
                'branch_id',
                'credit_note_date',
                'currency',
                'status',
                'grand_total',
                'created_at',
                'updated_at',
            ],
            'sort_map' => [
                'customer' => 'customer_id',
                'branch' => 'branch_id',
            ],
        ],
        'ap_payments' => [
            'model_class' => ApPayment::class,
            'with' => [
                'supplier',
                'branch',
                'fiscalYear',
                'bankAccount',
                'approver',
                'creator',
                'confirmer',
                'allocations.supplierBill',
            ],
            'search_fields' => [
                'payment_number',
                'reference',
                'notes',
            ],
            'filter_keys' => [
                'supplier_id',
                'branch_id',
                'fiscal_year_id',
                'status',
                'currency',
                'payment_date_from',
                'payment_date_to',
                'grand_total_min',
                'grand_total_max',
            ],
            'default_sort_by' => 'created_at',
            'allowed_sorts' => [
                'id',
                'payment_number',
                'supplier_id',
                'branch_id',
                'payment_date',
                'currency',
                'status',
                'grand_total',
                'created_at',
                'updated_at',
            ],
            'sort_map' => [
                'supplier' => 'supplier_id',
                'branch' => 'branch_id',
            ],
        ],
        'goods_receipts' => [
            'model_class' => GoodsReceipt::class,
            'with' => [
                'purchaseOrder',
                'warehouse',
                'creator',
                'confirmer',
                'items.product',
                'items.unit',
            ],
            'search_fields' => [
                'gr_number',
                'supplier_delivery_note',
                'notes',
            ],
            'filter_keys' => [
                'purchase_order_id',
                'warehouse_id',
                'received_by',
                'status',
                'receipt_date_from',
                'receipt_date_to',
            ],
            'default_sort_by' => 'created_at',
            'allowed_sorts' => [
                'id',
                'gr_number',
                'purchase_order_id',
                'warehouse_id',
                'receipt_date',
                'status',
                'created_at',
                'updated_at',
            ],
            'sort_map' => [
                'purchase_order' => 'purchase_order_id',
                'warehouse' => 'warehouse_id',
            ],
        ],
        'purchase_orders' => [
            'model_class' => PurchaseOrder::class,
            'with' => [
                'supplier',
                'warehouse',
                'creator',
                'items.product',
                'items.unit',
            ],
            'search_fields' => [
                'po_number',
                'payment_terms',
                'shipping_address',
                'notes',
            ],
            'filter_keys' => [
                'supplier_id',
                'warehouse_id',
                'status',
                'order_date_from',
                'order_date_to',
                'expected_delivery_date_from',
                'expected_delivery_date_to',
            ],
            'default_sort_by' => 'created_at',
            'allowed_sorts' => [
                'id',
                'po_number',
                'supplier_id',
                'warehouse_id',
                'order_date',
                'expected_delivery_date',
                'status',
                'grand_total',
                'created_at',
                'updated_at',
            ],
            'sort_map' => [
                'supplier' => 'supplier_id',
                'warehouse' => 'warehouse_id',
            ],
        ],
        'purchase_requests' => [
            'model_class' => PurchaseRequest::class,
            'with' => [
                'branch',
                'department',
                'requester',
                'creator',
                'items.product',
                'items.unit',
            ],
            'search_fields' => [
                'pr_number',
                'notes',
                'rejection_reason',
            ],
            'filter_keys' => [
                'branch_id',
                'department_id',
                'requested_by',
                'priority',
                'status',
                'request_date_from',
                'request_date_to',
                'required_date_from',
                'required_date_to',
            ],
            'default_sort_by' => 'created_at',
            'allowed_sorts' => [
                'id',
                'pr_number',
                'branch_id',
                'department_id',
                'requested_by',
                'priority',
                'status',
                'request_date',
                'required_date',
                'created_at',
                'updated_at',
            ],
            'sort_map' => [
                'branch' => 'branch_id',
                'department' => 'department_id',
                'requester' => 'requested_by',
            ],
        ],
        'supplier_bills' => [
            'model_class' => SupplierBill::class,
            'with' => [
                'supplier',
                'branch',
                'fiscalYear',
                'creator',
                'confirmer',
                'items.product',
                'items.account',
            ],
            'search_fields' => [
                'bill_number',
                'notes',
            ],
            'filter_keys' => [
                'supplier_id',
                'branch_id',
                'fiscal_year_id',
                'status',
                'currency',
                'bill_date_from',
                'bill_date_to',
                'due_date_from',
                'due_date_to',
                'grand_total_min',
                'grand_total_max',
                'amount_due_min',
                'amount_due_max',
            ],
            'default_sort_by' => 'created_at',
            'allowed_sorts' => [
                'id',
                'bill_number',
                'supplier_id',
                'branch_id',
                'bill_date',
                'due_date',
                'currency',
                'status',
                'grand_total',
                'amount_due',
                'created_at',
                'updated_at',
            ],
            'sort_map' => [
                'supplier' => 'supplier_id',
                'branch' => 'branch_id',
            ],
        ],
        'supplier_returns' => [
            'model_class' => SupplierReturn::class,
            'with' => [
                'purchaseOrder',
                'goodsReceipt',
                'supplier',
                'warehouse',
                'creator',
                'items.product',
                'items.unit',
            ],
            'search_fields' => [
                'return_number',
                'notes',
            ],
            'filter_keys' => [
                'purchase_order_id',
                'goods_receipt_id',
                'supplier_id',
                'warehouse_id',
                'reason',
                'status',
                'return_date_from',
                'return_date_to',
            ],
            'default_sort_by' => 'created_at',
            'allowed_sorts' => [
                'id',
                'return_number',
                'purchase_order_id',
                'goods_receipt_id',
                'supplier_id',
                'warehouse_id',
                'return_date',
                'reason',
                'status',
                'created_at',
                'updated_at',
            ],
            'sort_map' => [
                'purchase_order' => 'purchase_order_id',
                'goods_receipt' => 'goods_receipt_id',
                'supplier' => 'supplier_id',
                'warehouse' => 'warehouse_id',
            ],
        ],
    ];

    /**
     * @return array{
     *     model_class: class-string<Model>,
     *     with: array<int, string>,
     *     search_fields: array<int, string>,
     *     filter_keys: array<int, string>,
     *     default_sort_by: string,
     *     allowed_sorts: array<int, string>,
     *     sort_map: array<string, string>
     * }
     */
    public static function for(string $key): array
    {
        if (! isset(self::CONFIGURATIONS[$key])) {
            throw new InvalidArgumentException('Unknown transaction mapped index configuration: ' . $key);
        }

        return self::CONFIGURATIONS[$key];
    }
}
