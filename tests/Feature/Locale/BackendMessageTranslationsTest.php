<?php

use App\Http\Controllers\Api\AuthController;
use App\Imports\BankStatementImport;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('locale');

/**
 * @return array<string, string>
 */
function backendMessageTranslations(string $file): array
{
    return json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);
}

test('user guide not found message defaults to english without locale header', function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);

    getJson('/api/user-guide/does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('message', 'Guide not found.');
});

test('user guide not found message is indonesian with X-Locale id', function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);

    getJson('/api/user-guide/does-not-exist', ['X-Locale' => 'id'])
        ->assertNotFound()
        ->assertJsonPath('message', 'Panduan tidak ditemukan.');
});

test('auth me unauthenticated message is translated and keeps english text by default', function () {
    $controller = app(AuthController::class);
    $request = Request::create('/api/me', 'GET');

    $response = $controller->me($request);
    expect($response->getStatusCode())->toBe(401);
    expect(json_decode($response->getContent(), true)['message'])->toBe('Unauthenticated');

    app()->setLocale('id');
    $idResponse = $controller->me($request);
    app()->setLocale(config('app.locale'));
    expect(json_decode($idResponse->getContent(), true)['message'])->toBe('Tidak terautentikasi');
});

function createBalancedDraftJournalForLocale(): int
{
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);
    $coa = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'active',
    ]);
    $accounts = Account::factory()->count(2)->create(['coa_version_id' => $coa->id]);
    $entry = JournalEntry::factory()->create(['status' => 'draft', 'fiscal_year_id' => $fiscalYear->id]);

    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $accounts[0]->id,
        'debit' => 1000,
        'credit' => 0,
    ]);
    JournalEntryLine::create([
        'journal_entry_id' => $entry->id,
        'account_id' => $accounts[1]->id,
        'debit' => 0,
        'credit' => 1000,
    ]);

    return $entry->id;
}

test('posting journal success message keeps english text and interpolates count', function () {
    $user = createTestUserWithPermissions(['posting_journal', 'posting_journal.edit']);
    Sanctum::actingAs($user, ['*']);
    $entryId = createBalancedDraftJournalForLocale();

    postJson('/api/posting-journals/post', ['ids' => [$entryId]])
        ->assertOk()
        ->assertJsonPath('message', 'Successfully posted 1 journal entries.');
});

test('posting journal success message is indonesian with X-Locale id', function () {
    $user = createTestUserWithPermissions(['posting_journal', 'posting_journal.edit']);
    Sanctum::actingAs($user, ['*']);
    $entryId = createBalancedDraftJournalForLocale();

    postJson('/api/posting-journals/post', ['ids' => [$entryId]], ['X-Locale' => 'id'])
        ->assertOk()
        ->assertJsonPath('message', 'Berhasil memposting 1 entri jurnal.');
});

test('bank statement import row errors keep english text and are indonesian with id', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $row = collect(['description' => 'Biaya admin']);
    $mapping = ['date' => 'date', 'description' => 'description', 'amount' => 'amount'];

    $import = new BankStatementImport($reconciliation, $mapping);
    $import->collection(new Collection([$row]));

    expect($import->errors[0]['message'])->toBe('Transaction date is required.');

    app()->setLocale('id');
    $idImport = new BankStatementImport($reconciliation, $mapping);
    $idImport->collection(new Collection([$row]));
    app()->setLocale(config('app.locale'));

    expect($idImport->errors[0]['message'])->toBe('Tanggal transaksi wajib diisi.');
});

test('backend message translation files have matching keys', function () {
    $en = backendMessageTranslations('en.json');
    $id = backendMessageTranslations('id.json');

    $keys = [
        'user_guide_api.not_found',
        'user_api.updated',
        'employee_api.permissions_updated',
        'social_auth_api.unlinked',
        'social_auth_api.password_set',
        'auth_api.unauthenticated',
        'auth_api.logged_out',
        'posting_journal_api.posted',
        'admin_setting_api.updated',
        'admin_setting_api.test_email_sent',
        'admin_setting_api.test_email_failed',
        'bank_statement_import.date_required',
        'bank_statement_import.description_required',
        'bank_statement_import.invalid_date',
        'bank_statement_import.invalid_amount',
        'bank_statement_import.amount_required',
        'import_rows.save_failed',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($id)->toHaveKey($key);
    }

    expect($en['admin_setting_api.test_email_failed'])->toBe('Failed to send email: :detail');
    expect($en['import_rows.save_failed'])->toBe('Failed to save: :detail');
});
