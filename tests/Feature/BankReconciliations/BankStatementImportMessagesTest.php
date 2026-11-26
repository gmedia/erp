<?php

use App\Imports\BankStatementImport;
use App\Models\BankReconciliation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class)->group('bank-reconciliations');

/**
 * @param  array<string, string>  $mapping
 * @param  array<string, mixed>  $row
 */
function runBankStatementImportRow(array $mapping, array $row, ?BankReconciliation $reconciliation = null): BankStatementImport
{
    $reconciliation ??= BankReconciliation::factory()->create();
    $import = new BankStatementImport($reconciliation, $mapping);
    $import->collection(new Collection([collect($row)]));

    return $import;
}

test('bank statement import reports missing description in english and indonesian', function () {
    $mapping = ['date' => 'date', 'description' => 'description', 'amount' => 'amount'];
    $row = ['date' => '2026-01-05', 'description' => '', 'amount' => '100'];

    $import = runBankStatementImportRow($mapping, $row);
    expect($import->errors[0]['field'])->toBe('description');
    expect($import->errors[0]['message'])->toBe('Description is required.');

    app()->setLocale('id');
    $idImport = runBankStatementImportRow($mapping, $row);
    app()->setLocale(config('app.locale'));
    expect($idImport->errors[0]['message'])->toBe('Deskripsi wajib diisi.');
});

test('bank statement import reports invalid date with raw value in english and indonesian', function () {
    $mapping = ['date' => 'date', 'description' => 'description', 'amount' => 'amount'];
    $row = ['date' => 'bukan-tanggal', 'description' => 'Biaya admin', 'amount' => '100'];

    $import = runBankStatementImportRow($mapping, $row);
    expect($import->errors[0]['field'])->toBe('date');
    expect($import->errors[0]['message'])->toBe("Invalid date format: 'bukan-tanggal'.");

    app()->setLocale('id');
    $idImport = runBankStatementImportRow($mapping, $row);
    app()->setLocale(config('app.locale'));
    expect($idImport->errors[0]['message'])->toBe("Format tanggal tidak valid: 'bukan-tanggal'.");
});

test('bank statement import reports invalid amount with raw value in english and indonesian', function () {
    $mapping = ['date' => 'date', 'description' => 'description', 'amount' => 'amount'];
    $row = ['date' => '2026-01-05', 'description' => 'Biaya admin', 'amount' => 'seratus'];

    $import = runBankStatementImportRow($mapping, $row);
    expect($import->errors[0]['field'])->toBe('amount');
    expect($import->errors[0]['message'])->toBe("Invalid amount: 'seratus'.");

    app()->setLocale('id');
    $idImport = runBankStatementImportRow($mapping, $row);
    app()->setLocale(config('app.locale'));
    expect($idImport->errors[0]['message'])->toBe("Jumlah tidak valid: 'seratus'.");
});

test('bank statement import reports zero debit and credit in english and indonesian', function () {
    $mapping = ['date' => 'date', 'description' => 'description', 'debit' => 'debit', 'credit' => 'credit'];
    $row = ['date' => '2026-01-05', 'description' => 'Biaya admin', 'debit' => '0', 'credit' => '0'];

    $import = runBankStatementImportRow($mapping, $row);
    expect($import->errors[0]['field'])->toBe('amount');
    expect($import->errors[0]['message'])->toBe('At least one of debit or credit must be greater than zero.');
    expect($import->skippedCount)->toBe(1);

    app()->setLocale('id');
    $idImport = runBankStatementImportRow($mapping, $row);
    app()->setLocale(config('app.locale'));
    expect($idImport->errors[0]['message'])->toBe('Salah satu dari debit atau kredit harus lebih besar dari nol.');
});

test('bank statement import reports save failure detail when the row cannot be persisted', function () {
    // Reconciliation id that does not exist: the item insert violates the FK and throws.
    $missingReconciliation = BankReconciliation::factory()->make(['id' => 999999999]);
    $mapping = ['date' => 'date', 'description' => 'description', 'amount' => 'amount'];
    $row = ['date' => '2026-01-05', 'description' => 'Biaya admin', 'amount' => '100'];

    $import = runBankStatementImportRow($mapping, $row, $missingReconciliation);
    expect($import->importedCount)->toBe(0);
    expect($import->errors[0]['field'])->toBe('System');
    expect($import->errors[0]['message'])->toStartWith('Failed to save: ');
    expect(strlen($import->errors[0]['message']))->toBeGreaterThan(strlen('Failed to save: '));

    app()->setLocale('id');
    $idImport = runBankStatementImportRow($mapping, $row, $missingReconciliation);
    app()->setLocale(config('app.locale'));
    expect($idImport->errors[0]['message'])->toStartWith('Gagal menyimpan: ');
});
