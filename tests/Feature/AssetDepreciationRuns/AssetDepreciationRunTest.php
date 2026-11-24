<?php

use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetDepreciationLine;
use App\Models\AssetDepreciationRun;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('asset-depreciation-runs');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['asset_depreciation_run', 'asset_depreciation_run.edit']);
    Sanctum::actingAs($this->user);

    $this->fiscalYear = FiscalYear::factory()->create([
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'status' => 'open',
    ]);
});

test('user can view depreciation runs page', function () {
    $response = get('/asset-depreciation-runs');
    $response->assertStatus(200);
});

test('user can list depreciation runs with filters', function () {
    $otherFiscalYear = FiscalYear::factory()->create([
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'status' => 'open',
    ]);

    AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
        'status' => 'calculated',
    ]);
    AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'status' => 'draft',
    ]);
    AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $otherFiscalYear->id,
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
        'status' => 'calculated',
    ]);

    $response = getJson('/api/asset-depreciation-runs?fiscal_year_id=' . $this->fiscalYear->id . '&status=calculated&start_date=2024-02-01&end_date=2024-02-29&sort_by=period_start&sort_direction=asc&per_page=10');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.fiscal_year.id', $this->fiscalYear->id)
        ->assertJsonPath('data.0.status', 'calculated')
        ->assertJsonPath('data.0.period_start', '2024-02-01')
        ->assertJsonPath('data.0.lines_count', 0)
        ->assertJsonPath('meta.total', 1);
});

test('user can calculate depreciation', function () {
    Asset::factory()->create([
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'useful_life_months' => 60,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 12000000,
        'accumulated_depreciation' => 0,
    ]);

    $response = postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('message', 'Depreciation calculated successfully.');

    $this->assertDatabaseHas('asset_depreciation_runs', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'status' => 'calculated',
    ]);

    $run = AssetDepreciationRun::first();
    $this->assertDatabaseHas('asset_depreciation_lines', [
        'asset_depreciation_run_id' => $run->id,
        'amount' => 200000,
    ]);
});

test('user can view depreciation run lines', function () {
    $run = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'status' => 'calculated',
    ]);

    $response = getJson("/api/asset-depreciation-runs/{$run->id}/lines");
    $response->assertStatus(200);
});

test('user can post depreciation run to journal', function () {
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);
    $account = Account::factory()->create(['coa_version_id' => $coaVersion->id, 'is_active' => true]);

    $asset = Asset::factory()->create([
        'purchase_cost' => 12000000,
        'salvage_value' => 0,
        'useful_life_months' => 60,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 12000000,
        'accumulated_depreciation' => 0,
        'depreciation_expense_account_id' => $account->id,
        'accumulated_depr_account_id' => $account->id,
    ]);

    $run = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'status' => 'calculated',
    ]);

    AssetDepreciationLine::factory()->create([
        'asset_depreciation_run_id' => $run->id,
        'asset_id' => $asset->id,
        'amount' => 200000,
        'accumulated_before' => 0,
        'accumulated_after' => 200000,
        'book_value_after' => 11800000,
    ]);

    $response = postJson("/api/asset-depreciation-runs/{$run->id}/post");

    $response->assertStatus(200);
    $response->assertJsonPath('message', 'Depreciation successfully posted to journal.');

    $this->assertDatabaseHas('asset_depreciation_runs', [
        'id' => $run->id,
        'status' => 'posted',
    ]);

    $run->refresh();
    $this->assertNotNull($run->journal_entry_id);

    // A posted depreciation run must leave a posted journal so the expense
    // and accumulated depreciation reach the GL (W7-05).
    $this->assertDatabaseHas('journal_entries', [
        'id' => $run->journal_entry_id,
        'status' => 'posted',
    ]);
});

test('depreciation posting attributes journal lines per asset branch and injects clearing', function () {
    $branchA = Branch::factory()->create();
    $branchB = Branch::factory()->create();
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);
    $expenseAccount = Account::factory()->create(['coa_version_id' => $coaVersion->id, 'is_active' => true]);
    $accumulatedAccount = Account::factory()->create(['coa_version_id' => $coaVersion->id, 'is_active' => true]);
    $clearingAccount = Account::factory()->create(['code' => '1999-IBC', 'coa_version_id' => $coaVersion->id, 'is_active' => true]);

    $assetA = Asset::factory()->create([
        'branch_id' => $branchA->id,
        'depreciation_expense_account_id' => $expenseAccount->id,
        'accumulated_depr_account_id' => $accumulatedAccount->id,
    ]);
    $assetB = Asset::factory()->create([
        'branch_id' => $branchB->id,
        'depreciation_expense_account_id' => $expenseAccount->id,
        'accumulated_depr_account_id' => $accumulatedAccount->id,
    ]);

    $run = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'status' => 'calculated',
    ]);

    foreach ([$assetA, $assetB] as $asset) {
        AssetDepreciationLine::factory()->create([
            'asset_depreciation_run_id' => $run->id,
            'asset_id' => $asset->id,
            'amount' => 100000,
            'accumulated_before' => 0,
            'accumulated_after' => 100000,
            'book_value_after' => 0,
        ]);
    }

    postJson("/api/asset-depreciation-runs/{$run->id}/post")->assertStatus(200);

    $run->refresh();
    $lines = JournalEntryLine::where('journal_entry_id', $run->journal_entry_id)->get();

    // Each branch's depreciation expense + accumulated lines are tagged to that branch.
    expect($lines->where('branch_id', $branchA->id)->where('account_id', $expenseAccount->id))->toHaveCount(1);
    expect($lines->where('branch_id', $branchB->id)->where('account_id', $expenseAccount->id))->toHaveCount(1);

    // Per branch the entry self-balances (expense debit == accumulated credit), so
    // no clearing line is needed for this symmetric case.
    foreach ([$branchA->id, $branchB->id] as $branchId) {
        $branchLines = $lines->where('branch_id', $branchId);
        $debit = $branchLines->sum(fn ($l) => (int) round(((float) $l->debit) * 100));
        $credit = $branchLines->sum(fn ($l) => (int) round(((float) $l->credit) * 100));
        expect($debit)->toBe($credit);
    }
});

test('calculate rejects a period that overlaps an existing run (422)', function () {
    AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'status' => 'calculated',
    ]);

    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-15',
        'period_end' => '2024-02-15',
    ])->assertStatus(422);

    expect(AssetDepreciationRun::count())->toBe(1);
});

test('calculate accepts an adjacent period that does not overlap', function () {
    AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'status' => 'posted',
    ]);

    // Run Januari sudah posted, jadi run Februari boleh dihitung (review 443 butir 2).
    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
    ])->assertStatus(200);
});

test('calculate uses purchase cost minus accumulated depreciation when book value is empty', function () {
    $asset = Asset::factory()->create([
        'purchase_cost' => 1000,
        'salvage_value' => 0,
        'useful_life_months' => 10,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => null,
        'accumulated_depreciation' => 300,
    ]);

    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-04-01',
        'period_end' => '2024-04-30',
    ])->assertStatus(200);

    $this->assertDatabaseHas('asset_depreciation_lines', [
        'asset_id' => $asset->id,
        'amount' => 100,
        'accumulated_before' => 300,
        'accumulated_after' => 400,
        'book_value_after' => 600,
    ]);
});

test('last month consumes the whole rounding remainder so book value reaches salvage', function () {
    $asset = Asset::factory()->create([
        'purchase_cost' => 1000,
        'salvage_value' => 0,
        'useful_life_months' => 3,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 334,
        'accumulated_depreciation' => 666,
    ]);

    // Dua bulan pertama sudah dibebankan (333 + 333 = 666) sehingga bulan ketiga adalah bulan terakhir.
    // Satu line per aset per run (unik), jadi tiap bulan butuh run sendiri.
    foreach (['2024-01-01', '2024-02-01'] as $periodStart) {
        $priorRun = AssetDepreciationRun::factory()->create([
            'fiscal_year_id' => $this->fiscalYear->id,
            'period_start' => $periodStart,
            'period_end' => Carbon::parse($periodStart)->endOfMonth()->toDateString(),
            'status' => 'posted',
        ]);
        AssetDepreciationLine::factory()->create([
            'asset_depreciation_run_id' => $priorRun->id,
            'asset_id' => $asset->id,
            'amount' => 333,
        ]);
    }

    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-03-01',
        'period_end' => '2024-03-31',
    ])->assertStatus(200);

    $this->assertDatabaseHas('asset_depreciation_lines', [
        'asset_id' => $asset->id,
        'amount' => 334,
        'accumulated_after' => 1000,
        'book_value_after' => 0,
    ]);
});

test('calculate rejects a run that spans more than one calendar month (422)', function () {
    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-05-01',
        'period_end' => '2024-07-31',
    ])->assertStatus(422)->assertJsonValidationErrors('period_end');

    expect(AssetDepreciationRun::count())->toBe(0);
});

test('a monthly run never charges more than one monthly amount before the final month', function () {
    $asset = Asset::factory()->create([
        'purchase_cost' => 1000,
        'salvage_value' => 0,
        'useful_life_months' => 10,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 190,
        'accumulated_depreciation' => 810,
    ]);

    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-03-01',
        'period_end' => '2024-03-31',
    ])->assertStatus(200);

    $this->assertDatabaseHas('asset_depreciation_lines', [
        'asset_id' => $asset->id,
        'amount' => 100,
        'book_value_after' => 90,
    ]);
});

test('void run lines do not count toward remaining life months', function () {
    // Umur 3 bulan (monthly 333). Bulan 1 posted = 333, plus satu baris dari run void.
    // Sisa bulan yang benar = 2, jadi bulan 2 hanya membebankan 333 (bukan sisa 667).
    $asset = Asset::factory()->create([
        'purchase_cost' => 1000,
        'salvage_value' => 0,
        'useful_life_months' => 3,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 667,
        'accumulated_depreciation' => 333,
    ]);

    $postedRun = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'status' => 'posted',
    ]);
    AssetDepreciationLine::create([
        'asset_depreciation_run_id' => $postedRun->id,
        'asset_id' => $asset->id,
        'amount' => 333,
        'accumulated_before' => 0,
        'accumulated_after' => 333,
        'book_value_after' => 667,
    ]);

    $voidRun = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
        'status' => 'void',
    ]);
    AssetDepreciationLine::create([
        'asset_depreciation_run_id' => $voidRun->id,
        'asset_id' => $asset->id,
        'amount' => 333,
        'accumulated_before' => 333,
        'accumulated_after' => 666,
        'book_value_after' => 334,
    ]);

    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-03-01',
        'period_end' => '2024-03-31',
    ])->assertStatus(200);

    $this->assertDatabaseHas('asset_depreciation_lines', [
        'asset_id' => $asset->id,
        'asset_depreciation_run_id' => AssetDepreciationRun::where('status', 'calculated')->value('id'),
        'amount' => 333,
        'book_value_after' => 334,
    ]);
});

test('review 443 butir 1: calculated run that is not posted does not reduce remaining life months', function () {
    // Umur 3 bulan, harga 1000. Run bulan 1 hanya calculated (belum posted), sehingga
    // akumulasi aset masih 0. Sisa bulan harus tetap 3 (bukan 2) dan bulan ini = 333.
    $asset = Asset::factory()->create([
        'purchase_cost' => 1000,
        'salvage_value' => 0,
        'useful_life_months' => 3,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 1000,
        'accumulated_depreciation' => 0,
    ]);

    $calculatedRun = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'status' => 'calculated',
    ]);
    AssetDepreciationLine::factory()->create([
        'asset_depreciation_run_id' => $calculatedRun->id,
        'asset_id' => $asset->id,
        'amount' => 333,
        'accumulated_before' => 0,
        'accumulated_after' => 333,
        'book_value_after' => 667,
    ]);

    // Run bulan 1 belum diposting, jadi run baru harus ditolak (butir 2), bukan dihitung.
    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
    ])->assertStatus(422);

    expect(AssetDepreciationRun::count())->toBe(1);
});

test('review 443 butir 2: calculate rejects while an earlier calculated run is still unposted (422)', function () {
    $asset = Asset::factory()->create([
        'purchase_cost' => 1000,
        'salvage_value' => 0,
        'useful_life_months' => 3,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 1000,
        'accumulated_depreciation' => 0,
    ]);

    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
    ])->assertStatus(200);

    // Run Januari masih calculated (belum diposting) dan berisi baris untuk aset ini.
    postJson('/api/asset-depreciation-runs/calculate', [
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
    ])->assertStatus(422)->assertJsonValidationErrors('period_start');

    expect(AssetDepreciationRun::count())->toBe(1);
    expect(AssetDepreciationLine::where('asset_id', $asset->id)->count())->toBe(1);
});

test('review 443 butir 3: posting rejects a line whose accumulated_before no longer matches the asset', function () {
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);
    $account = Account::factory()->create(['coa_version_id' => $coaVersion->id, 'is_active' => true]);

    $asset = Asset::factory()->create([
        'purchase_cost' => 1000,
        'salvage_value' => 0,
        'useful_life_months' => 3,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 667,
        'accumulated_depreciation' => 333,
        'depreciation_expense_account_id' => $account->id,
        'accumulated_depr_account_id' => $account->id,
    ]);

    // Baris dihitung ketika akumulasi masih 0, lalu akumulasi aset berubah lewat jalur lain.
    $run = AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
        'status' => 'calculated',
        'journal_entry_id' => null,
    ]);
    AssetDepreciationLine::factory()->create([
        'asset_depreciation_run_id' => $run->id,
        'asset_id' => $asset->id,
        'amount' => 333,
        'accumulated_before' => 0,
        'accumulated_after' => 333,
        'book_value_after' => 667,
    ]);

    postJson("/api/asset-depreciation-runs/{$run->id}/post")->assertStatus(422);

    expect($run->fresh()->status)->toBe('calculated');
    expect($run->fresh()->journal_entry_id)->toBeNull();
    expect((float) $asset->fresh()->accumulated_depreciation)->toBe(333.0);
});

test('review 443 butir 4: n sequential calculate+post months total exactly purchase cost minus salvage', function () {
    $asset = Asset::factory()->create([
        'purchase_cost' => 1000,
        'salvage_value' => 100,
        'useful_life_months' => 7,
        'depreciation_start_date' => '2024-01-01',
        'depreciation_method' => 'straight_line',
        'status' => 'active',
        'book_value' => 1000,
        'accumulated_depreciation' => 0,
        'depreciation_expense_account_id' => null,
        'accumulated_depr_account_id' => null,
    ]);

    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);
    $account = Account::factory()->create(['coa_version_id' => $coaVersion->id, 'is_active' => true]);
    $asset->update([
        'depreciation_expense_account_id' => $account->id,
        'accumulated_depr_account_id' => $account->id,
    ]);

    for ($month = 1; $month <= 7; $month++) {
        $periodStart = Carbon::create(2024, $month, 1);

        postJson('/api/asset-depreciation-runs/calculate', [
            'fiscal_year_id' => $this->fiscalYear->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodStart->copy()->endOfMonth()->toDateString(),
        ])->assertStatus(200);

        $run = AssetDepreciationRun::where('period_start', $periodStart->toDateString())->firstOrFail();
        postJson("/api/asset-depreciation-runs/{$run->id}/post")->assertStatus(200);
    }

    $asset->refresh();
    expect((float) $asset->accumulated_depreciation)->toBe(900.0);
    expect((float) $asset->book_value)->toBe(100.0);
    expect(AssetDepreciationLine::where('asset_id', $asset->id)->count())->toBe(7);
});

test('every life month posts exactly one line and only the final month takes the remainder', function () {
    $coaVersion = CoaVersion::factory()->create(['status' => 'active']);
    $expenseAccount = Account::factory()->create(['coa_version_id' => $coaVersion->id, 'is_active' => true]);
    $cases = [[1000, 0, 3], [9500, 500, 12], [2000, 0, 7]];

    foreach ($cases as $index => [$cost, $salvage, $life]) {
        $year = 2024 + $index;
        $fiscalYear = FiscalYear::factory()->create([
            'start_date' => "{$year}-01-01",
            'end_date' => "{$year}-12-31",
            'status' => 'open',
        ]);
        $asset = Asset::factory()->create([
            'purchase_cost' => $cost,
            'salvage_value' => $salvage,
            'useful_life_months' => $life,
            'depreciation_start_date' => "{$year}-01-01",
            'depreciation_method' => 'straight_line',
            'status' => 'active',
            'book_value' => $cost,
            'accumulated_depreciation' => 0,
            'depreciation_expense_account_id' => $expenseAccount->id,
            'accumulated_depr_account_id' => $expenseAccount->id,
        ]);

        $depreciable = $cost - $salvage;
        $monthly = round($depreciable / $life);
        $accumulated = 0;

        for ($month = 1; $month <= $life; $month++) {
            $periodStart = Carbon::create($year, $month, 1);

            postJson('/api/asset-depreciation-runs/calculate', [
                'fiscal_year_id' => $fiscalYear->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodStart->copy()->endOfMonth()->toDateString(),
            ])->assertStatus(200);

            $amount = (float) AssetDepreciationLine::where('asset_id', $asset->id)->latest('id')->value('amount');
            $expected = $month < $life ? $monthly : $depreciable - $monthly * ($life - 1);

            // Posting sebelum bulan berikutnya agar akumulasi aset ikut terbarui (review 443 butir 2).
            $calculatedRun = AssetDepreciationRun::where('fiscal_year_id', $fiscalYear->id)
                ->where('period_start', $periodStart->toDateString())
                ->where('status', 'calculated')
                ->firstOrFail();
            postJson("/api/asset-depreciation-runs/{$calculatedRun->id}/post")->assertStatus(200);
            $asset->refresh();

            expect($amount)->toBe((float) $expected);
            $accumulated += $amount;
        }

        expect(AssetDepreciationLine::where('asset_id', $asset->id)->count())->toBe($life);
        expect($accumulated)->toBe((float) $depreciable);
    }
});
