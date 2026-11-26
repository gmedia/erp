<?php

namespace App\Exports\Concerns;

/**
 * Baris dan deklarasi kolom bersama untuk export stok (stock monitor, inventory valuation).
 *
 * Urutan kolom: kode/nama produk, kategori, [satuan], kode/nama gudang, cabang, qty, avg cost,
 * stock value, last movement. Kolom satuan hanya dipakai laporan valuasi (`$includeUnit`).
 */
trait MapsStockValuationExportRow
{
    use ExportsNumberFormats;

    /**
     * @return array<int, mixed>
     */
    protected function stockValuationRow(object $stock, bool $includeUnit = false): array
    {
        $row = [
            $stock->product->code ?? '-',
            $stock->product->name ?? '-',
            $stock->product->category->name ?? '-',
        ];

        if ($includeUnit) {
            $row[] = $stock->product->unit->name ?? '-';
        }

        array_push(
            $row,
            $stock->warehouse->code ?? '-',
            $stock->warehouse->name ?? '-',
            $stock->warehouse->branch->name ?? '-',
            $this->numericValue($stock->quantity_on_hand) ?? 0,
            $this->numericValue($stock->average_cost) ?? 0,
            $this->numericValue($stock->stock_value) ?? 0,
            $stock->moved_at?->format('Y-m-d H:i:s') ?? '-',
        );

        return $row;
    }

    /**
     * @return array<int, string>
     */
    protected function numericColumnHeadings(): array
    {
        return ['Quantity On Hand'];
    }

    /**
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return ['Average Cost', 'Stock Value'];
    }
}
