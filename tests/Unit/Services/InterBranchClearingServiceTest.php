<?php

namespace Tests\Unit\Services;

use App\Services\InterBranchClearingService;
use RuntimeException;

uses()->group('inter-branch-clearing');

beforeEach(function () {
    $this->service = new InterBranchClearingService;
    $this->clearingId = 999;
});

function netByBranch(array $lines): array
{
    $net = [];
    foreach ($lines as $line) {
        $key = $line['branch_id'] === null ? 'null' : (string) $line['branch_id'];
        $net[$key] = ($net[$key] ?? 0)
            + (int) round(((float) $line['debit']) * 100)
            - (int) round(((float) $line['credit']) * 100);
    }

    return $net;
}

test('single-branch balanced entry injects nothing', function () {
    $lines = [
        ['account_id' => 1, 'branch_id' => 5, 'debit' => 100, 'credit' => 0, 'memo' => null],
        ['account_id' => 2, 'branch_id' => 5, 'debit' => 0, 'credit' => 100, 'memo' => null],
    ];

    $result = $this->service->inject($lines, $this->clearingId);

    expect($result)->toHaveCount(2);
    expect(collect($result)->where('account_id', $this->clearingId))->toHaveCount(0);
});

test('null-branch single-group entry injects nothing (current data)', function () {
    $lines = [
        ['account_id' => 1, 'branch_id' => null, 'debit' => 100, 'credit' => 0, 'memo' => null],
        ['account_id' => 2, 'branch_id' => null, 'debit' => 0, 'credit' => 100, 'memo' => null],
    ];

    $result = $this->service->inject($lines, $this->clearingId);

    expect($result)->toHaveCount(2);
});

test('two-branch cash transfer injects balancing clearing lines', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 1, 'debit' => 0, 'credit' => 500, 'memo' => null],
        ['account_id' => 10, 'branch_id' => 2, 'debit' => 500, 'credit' => 0, 'memo' => null],
    ];

    $result = $this->service->inject($lines, $this->clearingId);

    $net = netByBranch($result);
    expect($net['1'])->toBe(0);
    expect($net['2'])->toBe(0);

    $clearing = collect($result)->where('account_id', $this->clearingId)->values();
    expect($clearing)->toHaveCount(2);
    $totalDebitCents = collect($result)->sum(fn ($l) => (int) round(((float) $l['debit']) * 100));
    $totalCreditCents = collect($result)->sum(fn ($l) => (int) round(((float) $l['credit']) * 100));
    expect($totalDebitCents)->toBe($totalCreditCents);
});

test('HQ centralized payment for two branches', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 9, 'debit' => 0, 'credit' => 300, 'memo' => null],
        ['account_id' => 50, 'branch_id' => 1, 'debit' => 100, 'credit' => 0, 'memo' => null],
        ['account_id' => 50, 'branch_id' => 2, 'debit' => 200, 'credit' => 0, 'memo' => null],
    ];

    $result = $this->service->inject($lines, $this->clearingId);

    $net = netByBranch($result);
    expect($net['9'])->toBe(0);
    expect($net['1'])->toBe(0);
    expect($net['2'])->toBe(0);

    $clearing = collect($result)->where('account_id', $this->clearingId);
    $clearingNetCents = $clearing->sum(fn ($l) => (int) round(((float) $l['debit']) * 100) - (int) round(((float) $l['credit']) * 100));
    expect($clearingNetCents)->toBe(0);
});

test('three branches with one zero-net branch', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 1, 'debit' => 100, 'credit' => 0, 'memo' => null],
        ['account_id' => 11, 'branch_id' => 1, 'debit' => 0, 'credit' => 100, 'memo' => null],
        ['account_id' => 12, 'branch_id' => 2, 'debit' => 0, 'credit' => 400, 'memo' => null],
        ['account_id' => 13, 'branch_id' => 3, 'debit' => 400, 'credit' => 0, 'memo' => null],
    ];

    $result = $this->service->inject($lines, $this->clearingId);

    $net = netByBranch($result);
    expect($net['1'])->toBe(0);
    expect($net['2'])->toBe(0);
    expect($net['3'])->toBe(0);

    $clearing = collect($result)->where('account_id', $this->clearingId);
    expect($clearing)->toHaveCount(2);
});

test('one-cent residual distributes without drift', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 9, 'debit' => 0, 'credit' => 100, 'memo' => null],
        ['account_id' => 50, 'branch_id' => 1, 'debit' => 33.33, 'credit' => 0, 'memo' => null],
        ['account_id' => 50, 'branch_id' => 2, 'debit' => 33.33, 'credit' => 0, 'memo' => null],
        ['account_id' => 50, 'branch_id' => 3, 'debit' => 33.34, 'credit' => 0, 'memo' => null],
    ];

    $result = $this->service->inject($lines, $this->clearingId);

    foreach (netByBranch($result) as $net) {
        expect($net)->toBe(0);
    }

    $clearingNetCents = collect($result)
        ->where('account_id', $this->clearingId)
        ->sum(fn ($l) => (int) round(((float) $l['debit']) * 100) - (int) round(((float) $l['credit']) * 100));
    expect($clearingNetCents)->toBe(0);
});

test('injection is idempotent (run twice equals once)', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 1, 'debit' => 0, 'credit' => 500, 'memo' => null],
        ['account_id' => 10, 'branch_id' => 2, 'debit' => 500, 'credit' => 0, 'memo' => null],
    ];

    $once = $this->service->inject($lines, $this->clearingId);
    $twice = $this->service->inject($once, $this->clearingId);

    expect($twice)->toEqual($once);
});

test('multi-branch entry where each branch self-balances injects nothing', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 1, 'debit' => 100, 'credit' => 0, 'memo' => null],
        ['account_id' => 11, 'branch_id' => 1, 'debit' => 0, 'credit' => 100, 'memo' => null],
        ['account_id' => 12, 'branch_id' => 2, 'debit' => 200, 'credit' => 0, 'memo' => null],
        ['account_id' => 13, 'branch_id' => 2, 'debit' => 0, 'credit' => 200, 'memo' => null],
    ];

    $result = $this->service->inject($lines, null);

    expect($result)->toHaveCount(4);
    expect(collect($result)->where('account_id', $this->clearingId))->toHaveCount(0);
});

test('throws when multi-branch entry has an unresolved branch line', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 1, 'debit' => 100, 'credit' => 0, 'memo' => null],
        ['account_id' => 11, 'branch_id' => null, 'debit' => 0, 'credit' => 100, 'memo' => null],
    ];

    expect(fn () => $this->service->inject($lines, $this->clearingId))
        ->toThrow(RuntimeException::class);
});

test('throws when multi-branch entry has no clearing account configured', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 1, 'debit' => 0, 'credit' => 500, 'memo' => null],
        ['account_id' => 10, 'branch_id' => 2, 'debit' => 500, 'credit' => 0, 'memo' => null],
    ];

    expect(fn () => $this->service->inject($lines, null))
        ->toThrow(RuntimeException::class);
});

test('guard throws on per-branch imbalance', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 1, 'debit' => 100, 'credit' => 0, 'memo' => null],
        ['account_id' => 11, 'branch_id' => 2, 'debit' => 0, 'credit' => 100, 'memo' => null],
    ];

    expect(fn () => $this->service->assertBalancedPerBranch($lines))
        ->toThrow(RuntimeException::class);
});

test('guard passes on balanced single-branch set', function () {
    $lines = [
        ['account_id' => 10, 'branch_id' => 1, 'debit' => 100, 'credit' => 0, 'memo' => null],
        ['account_id' => 11, 'branch_id' => 1, 'debit' => 0, 'credit' => 100, 'memo' => null],
    ];

    $this->service->assertBalancedPerBranch($lines);
    expect(true)->toBeTrue();
});

test('property: random balanced multi-branch sets self-balance per branch', function () {
    for ($i = 0; $i < 50; $i++) {
        $branchCount = random_int(1, 4);
        $lines = [];
        $globalCents = 0;
        for ($b = 1; $b <= $branchCount; $b++) {
            $amount = random_int(1, 100000);
            $lines[] = ['account_id' => 10, 'branch_id' => $b, 'debit' => $amount / 100, 'credit' => 0, 'memo' => null];
            $globalCents += $amount;
        }
        $lines[] = ['account_id' => 20, 'branch_id' => 1, 'debit' => 0, 'credit' => $globalCents / 100, 'memo' => null];

        $result = $this->service->inject($lines, $this->clearingId);

        foreach (netByBranch($result) as $net) {
            expect($net)->toBe(0);
        }

        $clearingNetCents = collect($result)
            ->where('account_id', $this->clearingId)
            ->sum(fn ($l) => (int) round(((float) $l['debit']) * 100) - (int) round(((float) $l['credit']) * 100));
        expect($clearingNetCents)->toBe(0);
    }
});
