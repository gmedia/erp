<?php

use App\Http\Requests\JournalEntries\UpdateJournalEntryRequest;
use App\Models\Account;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('journal-entries');

test('it authorizes any user', function () {
    $request = new UpdateJournalEntryRequest();
    expect($request->authorize())->toBeTrue();
});

test('it validates balanced updates', function () {
    $account1 = Account::factory()->create();
    $account2 = Account::factory()->create();

    $data = [
        'entry_date' => '2023-01-01',
        'description' => 'Updated Description',
        'lines' => [
            [
                'account_id' => $account1->id,
                'debit' => 200,
                'credit' => 0,
            ],
            [
                'account_id' => $account2->id,
                'debit' => 0,
                'credit' => 200,
            ],
        ],
    ];

    $request = new UpdateJournalEntryRequest();
    $request->merge($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('it fails unbalanced updates', function () {
    $account1 = Account::factory()->create();
    $account2 = Account::factory()->create();

    $data = [
        'entry_date' => '2023-01-01',
        'description' => 'Updated Unbalanced',
        'lines' => [
            [
                'account_id' => $account1->id,
                'debit' => 200,
                'credit' => 0,
            ],
            [
                'account_id' => $account2->id,
                'debit' => 0,
                'credit' => 150,
            ],
        ],
    ];

    $request = new UpdateJournalEntryRequest();
    $request->merge($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('lines'))->toBe('Total Debit and Total Credit must be equal.');
});
