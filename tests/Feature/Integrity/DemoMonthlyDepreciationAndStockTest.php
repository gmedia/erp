<?php

use App\Models\AssetDepreciationRun;
use App\Models\JournalEntry;
use App\Models\StockMovement;
use App\Support\Demo\DemoCalendar;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class)->group('demo-integrity');

/**
 * Butir 5-6 #571: penyusutan aset tiap bulan dalam jendela 13 bulan berasal dari run
 * terposting (alur aplikasi), dan stok on hand sama dengan jumlah pergerakan stok.
 */
it('has a posted depreciation run and its journal in every closed month of the 13-month window', function () {
    seed(DatabaseSeeder::class);

    // Run dibuat hanya untuk bulan yang sudah lewat (AssetDepreciationNormalizationSeeder),
    // jadi bulan berjalan dikecualikan dari pemeriksaan ini.
    $closedMonths = collect(DemoCalendar::months())
        ->filter(fn ($month) => $month->lessThan(now()->startOfMonth()));

    expect($closedMonths)->not->toBeEmpty();

    foreach ($closedMonths as $month) {
        $runs = AssetDepreciationRun::query()
            ->where('status', 'posted')
            ->whereDate('period_start', $month->copy()->startOfMonth()->toDateString())
            ->get();

        expect($runs)->not->toBeEmpty("Bulan {$month->format('Y-m')} tidak punya run penyusutan terposting");

        foreach ($runs as $run) {
            expect($run->journal_entry_id)
                ->not->toBeNull("Run penyusutan {$run->id} ({$month->format('Y-m')}) belum punya jurnal");

            expect(JournalEntry::query()->whereKey($run->journal_entry_id)->where('status', 'posted')->exists())
                ->toBeTrue("Jurnal penyusutan run {$run->id} tidak posted");
        }
    }
});

it('keeps stock on hand equal to the sum of stock movements per product and warehouse', function () {
    seed(DatabaseSeeder::class);

    $onHand = StockMovement::query()
        ->select('product_id', 'warehouse_id')
        ->selectRaw('SUM(quantity_in - quantity_out) as on_hand')
        ->groupBy('product_id', 'warehouse_id')
        ->get();

    expect($onHand)->not->toBeEmpty();

    foreach ($onHand as $row) {
        $lastBalance = StockMovement::query()
            ->where('product_id', $row->product_id)
            ->where('warehouse_id', $row->warehouse_id)
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->value('balance_after');

        expect(round((float) $row->on_hand, 4))
            ->toBe(round((float) $lastBalance, 4), "Stok on hand produk {$row->product_id} gudang {$row->warehouse_id} tidak sama dengan jumlah pergerakan");
    }

    expect(DB::table('stock_movements')->where('balance_after', '<', 0)->count())->toBe(0);
});
