<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostGoodsReceiptJournalAction;
use App\Actions\AccountingPosting\PostSupplierReturnJournalAction;
use App\Actions\GoodsReceipts\PostGoodsReceiptMovementsAction;
use App\Actions\SupplierReturns\PostSupplierReturnMovementsAction;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineStateLog;
use App\Models\PipelineTransition;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseOrderReceiptService;
use App\Support\Demo\DemoCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Aktivitas pembelian bulanan untuk jendela demo 13 bulan (#571).
 *
 * Tiap bulan mendapat PO (confirm langsung), penerimaan barang (GR confirmed) dan, pada
 * sebagian bulan, retur ke supplier (confirmed). Konfirmasi memakai action yang sama dengan
 * controller: jurnal GR/GR-IR dan jurnal retur, serta pergerakan stok lewat StockPostingService.
 * Retur hanya mengurangi stok yang baru diterima bulan itu, sehingga tidak pernah minus.
 * Utang supplier (AP) tidak berubah di sini: tagihan dibuat oleh ApMonthlyDemoSeeder.
 *
 * Idempoten: nomor deterministik PO-/GR-/SR-YYYYMM-NNNN dengan updateOrCreate. Volume dan
 * nilai mengikuti pola musiman DemoCalendar.
 */
class PurchasingMonthlyDemoSeeder extends Seeder
{
    public const PO_PREFIX = 'PO-';

    public const GR_PREFIX = 'GR-';

    public const RETURN_PREFIX = 'SR-';

    /** Dasar jumlah PO per bulan sebelum faktor volume. */
    private const ORDERS_PER_MONTH = 2;

    /** Harga satuan cadangan bila produk belum punya harga pokok. */
    private const FALLBACK_UNIT_PRICE = 12000;

    /** Pola alasan retur yang bergilir per bulan (nilai enum supplier_returns.reason). */
    private const RETURN_REASONS = ['defective', 'damaged', 'wrong_item', 'excess_quantity'];

    public function run(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id')
            ?? User::query()->value('id');
        $branch = Branch::query()->first();
        $warehouse = Warehouse::query()->first();
        $fallbackUnitId = Unit::query()->value('id');
        $suppliers = Supplier::query()->with('category')->orderBy('id')->get();
        $products = Product::query()->with('category')
            ->where('type', '!=', 'service')
            ->orderBy('id')
            ->get();

        $receiverEmployeeId = Employee::query()->orderBy('id')->value('id');

        if (! $adminUserId || ! $branch || ! $warehouse || ! $fallbackUnitId
            || ! $receiverEmployeeId || $suppliers->isEmpty() || $products->isEmpty()) {
            return;
        }

        // Hanya pemasok barang fisik (punya produk di kategorinya) yang dipilih bergilir,
        // agar tidak ada bulan yang kehilangan PO karena pemasok jasa tanpa barang.
        $physicalSuppliers = $suppliers
            ->filter(fn (Supplier $supplier) => $products->contains(
                fn (Product $product) => $this->supplierSuppliesProduct($supplier, $product),
            ))
            ->values();

        if ($physicalSuppliers->isEmpty()) {
            return;
        }

        $pipelines = $this->pipelineStates();

        if ($pipelines === null) {
            return;
        }

        $receiptService = app(PurchaseOrderReceiptService::class);

        foreach (DemoCalendar::months() as $index => $month) {
            $fiscalYear = FiscalYear::query()
                ->where('start_date', '<=', $month->toDateString())
                ->where('end_date', '>=', $month->toDateString())
                ->first();

            if ($fiscalYear === null) {
                continue;
            }

            $code = $month->format('Ym');
            $orderCount = DemoCalendar::volume($index, self::ORDERS_PER_MONTH);

            for ($n = 1; $n <= $orderCount; $n++) {
                $supplier = $physicalSuppliers[($index + $n) % $physicalSuppliers->count()];
                $supplierProducts = $products
                    ->filter(fn (Product $product) => $this->supplierSuppliesProduct($supplier, $product))
                    ->values();

                $orderDate = DemoCalendar::dateInMonth($index, 3 + $n * 6);
                $receiptDate = DemoCalendar::dateInMonth($index, 8 + $n * 6);

                $order = $this->seedOrder(
                    $code,
                    $n,
                    $index,
                    $supplier,
                    $warehouse,
                    $supplierProducts,
                    $fallbackUnitId,
                    $adminUserId,
                    $orderDate,
                );
                $receipt = $this->seedReceipt(
                    $code,
                    $n,
                    $order,
                    $warehouse,
                    $adminUserId,
                    $receiverEmployeeId,
                    $receiptDate,
                    $fallbackUnitId,
                );

                $this->syncOrderPipeline($pipelines, $order, $adminUserId, $orderDate);

                if ($n === 1) {
                    $this->seedReturn($code, $index, $order, $receipt, $warehouse, $adminUserId, $fallbackUnitId);
                }

                $receiptService->syncFromGoodsReceipt($receipt->refresh());
            }
        }
    }

    private function seedOrder(
        string $code,
        int $n,
        int $index,
        Supplier $supplier,
        Warehouse $warehouse,
        $supplierProducts,
        int $fallbackUnitId,
        int $adminUserId,
        Carbon $orderDate,
    ): PurchaseOrder {
        $lines = [];
        $subtotal = 0.0;

        foreach ($supplierProducts->take(2)->values() as $lineIndex => $product) {
            $quantity = (float) DemoCalendar::volume($index, 4 + $n * 2 + $lineIndex * 3);
            $unitPrice = (float) ($product->cost ?? self::FALLBACK_UNIT_PRICE);
            $lineTotal = round($quantity * $unitPrice, 2);
            $subtotal += $lineTotal;
            $lines[] = [$product, $quantity, $unitPrice, $lineTotal];
        }

        $taxAmount = round($subtotal * 0.11, 2);
        $grandTotal = $subtotal + $taxAmount;

        $order = PurchaseOrder::updateOrCreate(
            ['po_number' => sprintf('%s%s-%04d', self::PO_PREFIX, $code, $n)],
            [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => $orderDate->toDateString(),
                'expected_delivery_date' => $orderDate->copy()->addDays(5)->toDateString(),
                'payment_terms' => 'Net 30',
                'currency' => 'IDR',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => 0,
                'grand_total' => $grandTotal,
                'status' => 'fully_received',
                'notes' => 'Pesanan pembelian rutin bulanan',
                'shipping_address' => 'Gudang Utama',
                'approved_by' => null,
                'approved_at' => null,
                'created_by' => $adminUserId,
            ],
        );

        foreach ($lines as [$product, $quantity, $unitPrice, $lineTotal]) {
            PurchaseOrderItem::updateOrCreate(
                ['purchase_order_id' => $order->id, 'product_id' => $product->id],
                [
                    'purchase_request_item_id' => null,
                    'unit_id' => $product->unit_id ?? $fallbackUnitId,
                    'quantity' => $quantity,
                    'quantity_received' => 0,
                    'unit_price' => $unitPrice,
                    'discount_percent' => 0,
                    'tax_percent' => 11,
                    'line_total' => $lineTotal,
                    'notes' => 'Kebutuhan operasional rutin',
                ],
            );
        }

        return $order;
    }

    /**
     * GR confirmed untuk seluruh baris PO. Konfirmasi memicu jurnal GR/GR-IR dan stok masuk
     * lewat action yang sama dengan GoodsReceiptController.
     */
    private function seedReceipt(
        string $code,
        int $n,
        PurchaseOrder $order,
        Warehouse $warehouse,
        int $adminUserId,
        int $receiverEmployeeId,
        Carbon $receiptDate,
        int $fallbackUnitId,
    ): GoodsReceipt {
        $receipt = GoodsReceipt::updateOrCreate(
            ['gr_number' => sprintf('%s%s-%04d', self::GR_PREFIX, $code, $n)],
            [
                'purchase_order_id' => $order->id,
                'warehouse_id' => $warehouse->id,
                'receipt_date' => $receiptDate->toDateString(),
                'supplier_delivery_note' => sprintf('SJ-%s-%04d', $code, $n),
                'status' => 'confirmed',
                'notes' => 'Penerimaan barang sesuai pesanan pembelian rutin',
                // received_by mengacu ke employees.id (bukan user), seperti PurchasingSampleDataSeeder.
                'received_by' => $receiverEmployeeId,
                'confirmed_by' => $adminUserId,
                'confirmed_at' => $receiptDate->copy()->setTime(10, 0),
                'created_by' => $adminUserId,
            ],
        );

        /** @var PurchaseOrderItem $poItem */
        foreach ($order->items()->orderBy('id')->get() as $poItem) {
            $quantity = (float) $poItem->quantity;

            GoodsReceiptItem::updateOrCreate(
                ['goods_receipt_id' => $receipt->id, 'purchase_order_item_id' => $poItem->id],
                [
                    'product_id' => $poItem->product_id,
                    'unit_id' => $poItem->unit_id ?? $fallbackUnitId,
                    'quantity_received' => $quantity,
                    'quantity_accepted' => $quantity,
                    'quantity_rejected' => 0,
                    'unit_price' => $poItem->unit_price,
                    'notes' => 'Item penerimaan barang',
                ],
            );
        }

        // Posting hanya saat GR baru dikonfirmasi. Seed ulang melewatinya: periode bulan itu
        // sudah ditutup DemoPeriodClosingSeeder, dan StockPostingService menolak pergerakan
        // di periode tertutup walau barisnya sudah ada (idempotensi, #571).
        if ($receipt->journal_entry_id === null) {
            $receipt->load('items', 'warehouse');

            app(PostGoodsReceiptJournalAction::class)->execute($receipt);
            app(PostGoodsReceiptMovementsAction::class)->execute($receipt);
        }

        return $receipt->refresh();
    }

    /**
     * Retur confirmed satu baris dari GR bulan itu. Jumlahnya 1 unit, lebih kecil dari
     * kuantitas yang diterima, dan tanggalnya setelah penerimaan (stok tidak pernah minus).
     */
    private function seedReturn(
        string $code,
        int $index,
        PurchaseOrder $order,
        GoodsReceipt $receipt,
        Warehouse $warehouse,
        int $adminUserId,
        int $fallbackUnitId,
    ): void {
        /** @var GoodsReceiptItem|null $receiptItem */
        $receiptItem = $receipt->items()->orderBy('id')->first();

        if ($receiptItem === null) {
            return;
        }

        $supplierReturn = SupplierReturn::updateOrCreate(
            ['return_number' => sprintf('%s%s-0001', self::RETURN_PREFIX, $code)],
            [
                'purchase_order_id' => $order->id,
                'goods_receipt_id' => $receipt->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $warehouse->id,
                'return_date' => DemoCalendar::dateInMonth($index, 24)->toDateString(),
                'reason' => self::RETURN_REASONS[$index % count(self::RETURN_REASONS)],
                'status' => 'confirmed',
                'notes' => 'Retur barang ke supplier karena tidak sesuai spesifikasi',
                'confirmed_by' => $adminUserId,
                'confirmed_at' => DemoCalendar::dateInMonth($index, 24)->setTime(14, 0),
                'created_by' => $adminUserId,
            ],
        );

        SupplierReturnItem::updateOrCreate(
            [
                'supplier_return_id' => $supplierReturn->id,
                'goods_receipt_item_id' => $receiptItem->id,
            ],
            [
                'product_id' => $receiptItem->product_id,
                'unit_id' => $receiptItem->unit_id ?? $fallbackUnitId,
                'quantity_returned' => 1.00,
                'unit_price' => $receiptItem->unit_price,
                'notes' => 'Item retur ke supplier',
            ],
        );

        // Sama seperti GR: posting hanya saat retur baru dikonfirmasi (lihat seedReceipt).
        if ($supplierReturn->journal_entry_id === null) {
            $supplierReturn->load('items', 'warehouse');

            app(PostSupplierReturnJournalAction::class)->execute($supplierReturn);
            app(PostSupplierReturnMovementsAction::class)->execute($supplierReturn);
        }
    }

    /**
     * Status PO dicatat di pipeline po_lifecycle: draft → confirmed (konfirmasi langsung) →
     * fully_received (GR penuh). Riwayat dibentuk ulang setiap seed agar tidak menggandakan.
     *
     * @param  array<string, mixed>  $pipelines  pipeline, states, transitions (lihat pipelineStates())
     */
    private function syncOrderPipeline(
        array $pipelines,
        PurchaseOrder $order,
        int $adminUserId,
        Carbon $orderDate,
    ): void {
        $states = $pipelines['states'];
        $transitions = $pipelines['transitions'];

        $entityState = PipelineEntityState::updateOrCreate(
            [
                'pipeline_id' => $pipelines['pipeline']->id,
                'entity_type' => PurchaseOrder::class,
                'entity_id' => $order->id,
            ],
            [
                'current_state_id' => $states['fully_received']->id,
                'last_transitioned_by' => $adminUserId,
                'last_transitioned_at' => $orderDate->copy()->addDays(7)->setTime(12, 0),
                'metadata' => null,
            ],
        );

        PipelineStateLog::query()->where('pipeline_entity_state_id', $entityState->id)->delete();

        $steps = [
            [null, 'draft', null, 'Initial pipeline assignment', $orderDate->copy()->setTime(8, 0)],
            ['draft', 'confirmed', 'confirm_direct', null, $orderDate->copy()->setTime(9, 0)],
            [
                'confirmed',
                'fully_received',
                'full_receipt_direct',
                null,
                $orderDate->copy()->addDays(5)->setTime(10, 0),
            ],
        ];

        foreach ($steps as [$from, $to, $transitionCode, $comment, $at]) {
            PipelineStateLog::create([
                'pipeline_entity_state_id' => $entityState->id,
                'entity_type' => PurchaseOrder::class,
                'entity_id' => $order->id,
                'from_state_id' => $from !== null ? $states[$from]->id : null,
                'to_state_id' => $states[$to]->id,
                'transition_id' => $transitionCode !== null ? ($transitions[$transitionCode] ?? null)?->id : null,
                'comment' => $comment,
                'performed_by' => $adminUserId,
                'created_at' => $at,
            ]);
        }
    }

    /**
     * @return array{pipeline: Pipeline, states: array<string, PipelineState>, transitions: array<string, mixed>}|null
     */
    private function pipelineStates(): ?array
    {
        $pipeline = Pipeline::query()->where('code', 'po_lifecycle')->first();

        if ($pipeline === null) {
            return null;
        }

        $states = PipelineState::query()
            ->where('pipeline_id', $pipeline->id)
            ->get()
            ->keyBy('code')
            ->all();

        if (! isset($states['draft'], $states['confirmed'], $states['fully_received'])) {
            return null;
        }

        $transitions = PipelineTransition::query()
            ->where('pipeline_id', $pipeline->id)
            ->get()
            ->keyBy('code')
            ->all();

        return ['pipeline' => $pipeline, 'states' => $states, 'transitions' => $transitions];
    }

    private function supplierSuppliesProduct(Supplier $supplier, Product $product): bool
    {
        $allowed = DemoMasterData::SUPPLIER_PRODUCT_CATEGORY_MAP[$supplier->category->name] ?? [];

        return in_array($product->category->name, $allowed, true);
    }
}
