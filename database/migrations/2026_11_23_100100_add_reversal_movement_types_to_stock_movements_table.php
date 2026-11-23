<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Movement pembalik untuk dokumen yang dibatalkan setelah posting (Issue #356).
     */
    private const TYPES = [
        'goods_receipt',
        'supplier_return',
        'transfer_out',
        'transfer_in',
        'adjustment_in',
        'adjustment_out',
        'production_consume',
        'production_output',
        'sales',
        'sales_return',
        'goods_receipt_reversal',
        'supplier_return_reversal',
        'adjustment_reversal',
        'transfer_out_reversal',
        'transfer_in_reversal',
    ];

    public function up(): void
    {
        $this->setEnum(self::TYPES);
    }

    public function down(): void
    {
        $this->setEnum(array_slice(self::TYPES, 0, 10));
    }

    /**
     * @param  array<int, string>  $types
     */
    private function setEnum(array $types): void
    {
        $list = implode(',', array_map(fn (string $type): string => "'{$type}'", $types));

        DB::statement("ALTER TABLE stock_movements MODIFY movement_type ENUM({$list}) NOT NULL");
    }
};
