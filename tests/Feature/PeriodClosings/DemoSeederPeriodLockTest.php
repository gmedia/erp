<?php

use App\Models\FiscalYear;
use App\Models\PeriodClosing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('period-closings');

/**
 * Seeder demo yang memposting jurnal tidak boleh gagal karena guard penguncian (#423).
 * Periode closed milik data demo dibuka sementara selama seeder berjalan lalu
 * dikembalikan. Periode yang ditutup pengguna tidak boleh tersentuh.
 */
test('seeder demo selesai walau periode demo sudah ditutup, dan status periode dikembalikan', function () {
    test()->seed();

    $closedDemo = PeriodClosing::query()
        ->where('status', 'closed')
        ->where('closing_type', 'monthly')
        ->count();

    expect($closedDemo)->toBeGreaterThan(0);
});

test('periode yang ditutup pengguna tetap closed setelah seeder demo dijalankan ulang', function () {
    test()->seed();

    // Tahun fiskal khusus test, di luar tahun demo. Seeder demo memposting ke bulan 01-07
    // dan 12 di tahun demo, sehingga periode user di sana akan berbenturan dengan posting demo.
    $fiscalYear = FiscalYear::factory()->create([
        'name' => '2099',
        'start_date' => '2099-01-01',
        'end_date' => '2099-12-31',
        'status' => 'open',
    ]);
    $user = User::query()->firstOrFail();
    $userClosing = PeriodClosing::factory()->closed()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'closing_type' => 'monthly',
        'period_year' => 2099,
        'period_month' => 12,
        'closed_by' => $user->id,
        'created_by' => $user->id,
    ]);

    test()->seed();

    expect($userClosing->refresh()->status)->toBe('closed');
});
