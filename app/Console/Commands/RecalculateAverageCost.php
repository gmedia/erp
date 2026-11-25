<?php

namespace App\Console\Commands;

use App\Models\StockMovement;
use App\Services\StockPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill `average_cost_after` dan `unit_cost` kartu stok historis (Issue #495).
 *
 * Menghitung ulang rata-rata tertimbang bergerak per produk+gudang secara
 * berurutan (moved_at, id) dengan aturan yang sama seperti
 * StockPostingService::rebuildBalances(). Aman dijalankan berulang (idempoten):
 * baris yang sudah benar tidak ditulis ulang.
 */
class RecalculateAverageCost extends Command
{
    private const TOLERANCE = 0.005;

    /**
     * @var string
     */
    protected $signature = 'stock:recalculate-average-cost'
        . ' {--dry-run : Hitung dan tampilkan selisih tanpa menulis ke database}';

    protected $description = 'Hitung ulang rata-rata tertimbang bergerak kartu stok secara berurutan.'
        . ' Idempoten; gunakan --dry-run untuk melihat ringkasan selisih.';

    public function handle(StockPostingService $stockPosting): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — tidak ada baris yang ditulis.');
        }

        $changes = [];
        $scopeCount = 0;
        $movementCount = 0;

        $grouped = StockMovement::query()
            ->orderBy('product_id')
            ->orderBy('warehouse_id')
            ->orderBy('moved_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (StockMovement $movement): string => $movement->product_id . ':' . $movement->warehouse_id);

        foreach ($grouped as $scopeMovements) {
            $scopeCount++;
            $balance = 0.0;
            $averageCost = 0.0;

            foreach ($scopeMovements as $movement) {
                $movementCount++;
                $quantityIn = (float) $movement->quantity_in;
                $quantityOut = (float) $movement->quantity_out;
                $positiveBalance = max($balance, 0.0);
                $unitCost = (float) $movement->unit_cost;

                if ($quantityIn > 0) {
                    $averageCost = $positiveBalance + $quantityIn > 0
                        ? (($positiveBalance * $averageCost) + ($quantityIn * $unitCost))
                            / ($positiveBalance + $quantityIn)
                        : $unitCost;
                } elseif ($quantityOut > 0) {
                    $unitCost = $averageCost;
                }

                $balance += $quantityIn - $quantityOut;

                $newAverage = round($averageCost, 2);
                $newUnitCost = round($unitCost, 2);
                $oldAverage = $movement->average_cost_after === null
                    ? (float) $movement->unit_cost
                    : (float) $movement->average_cost_after;
                $oldUnitCost = (float) $movement->unit_cost;

                if (abs($oldAverage - $newAverage) < self::TOLERANCE
                    && abs($oldUnitCost - $newUnitCost) < self::TOLERANCE
                    && $movement->average_cost_after !== null) {
                    continue;
                }

                $changes[] = [
                    'movement' => $movement,
                    'average_cost_after' => $newAverage,
                    'unit_cost' => $newUnitCost,
                    // Selisih nilai kartu: beda biaya dikali qty yang bergerak.
                    'value_delta' => round(abs($oldUnitCost - $newUnitCost) * ($quantityIn + $quantityOut), 2),
                ];
            }
        }

        $this->table(
            ['Produk:Gudang', 'Movement', 'Unit cost lama', 'Unit cost baru', 'Selisih nilai'],
            collect($changes)->take(50)->map(fn (array $change): array => [
                $change['movement']->product_id . ':' . $change['movement']->warehouse_id,
                $change['movement']->id,
                number_format((float) $change['movement']->unit_cost, 2, ',', '.'),
                number_format($change['unit_cost'], 2, ',', '.'),
                number_format($change['value_delta'], 2, ',', '.'),
            ])->all(),
        );

        $this->info(sprintf(
            'Scope: %d, movement diperiksa: %d, perlu diperbarui: %d, total selisih nilai: %s',
            $scopeCount,
            $movementCount,
            count($changes),
            number_format(collect($changes)->sum('value_delta'), 2, ',', '.'),
        ));

        if ($dryRun) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes, $stockPosting): void {
            foreach ($changes as $change) {
                $change['movement']->forceFill([
                    'average_cost_after' => $change['average_cost_after'],
                    'unit_cost' => $change['unit_cost'],
                ])->saveQuietly();
            }

            // Issue #495: transfer_in memakai biaya kirim = unit_cost transfer_out baris yang
            // sama (avg asal saat dispatch). Setelah transfer_out dihitung ulang, sinkronkan
            // lalu hitung ulang scope tujuan agar avg tujuan ikut benar.
            $this->syncTransferInCosts($stockPosting);
        });

        $this->info('Selesai: ' . count($changes) . ' movement diperbarui.');

        return self::SUCCESS;
    }

    /**
     * Samakan unit_cost transfer_in dengan transfer_out baris yang sama (Issue #495),
     * lalu hitung ulang scope tujuan. Idempoten: baris yang sudah sama tidak ditulis.
     */
    private function syncTransferInCosts(StockPostingService $stockPosting): void
    {
        $scopes = [];

        StockMovement::query()
            ->where('movement_type', 'transfer_in')
            ->whereNotNull('source_line_id')
            ->orderBy('id')
            ->get()
            ->each(function (StockMovement $transferIn) use (&$scopes): void {
                $shipmentCost = StockMovement::query()
                    ->where('reference_type', $transferIn->reference_type)
                    ->where('reference_id', $transferIn->reference_id)
                    ->where('movement_type', 'transfer_out')
                    ->where('source_line_id', $transferIn->source_line_id)
                    ->value('unit_cost');

                $gap = abs((float) $transferIn->unit_cost - (float) $shipmentCost);

                if ($shipmentCost === null || $gap < self::TOLERANCE) {
                    return;
                }

                $transferIn->forceFill(['unit_cost' => round((float) $shipmentCost, 2)])->saveQuietly();
                $scopes[] = [(int) $transferIn->product_id, (int) $transferIn->warehouse_id];
            });

        if ($scopes !== []) {
            $stockPosting->rebuildBalances($scopes);
        }
    }
}
