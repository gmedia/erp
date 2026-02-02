<?php

use App\Http\Requests\JournalEntries\StoreJournalEntryRequest;
use App\Models\Account;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('journal-entries');

test('it authorizes any user', function () {
    $request = new StoreJournalEntryRequest();
    expect($request->authorize())->toBeTrue();
});

test('it validates required fields', function () {
    $request = new StoreJournalEntryRequest();
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->keys())->toContain('entry_date', 'description', 'lines');
});

test('it validates balanced debit and credit', function () {
    $account1 = Account::factory()->create();
    $account2 = Account::factory()->create();

    $data = [
        'entry_date' => '2023-01-01',
        'description' => 'Test Entry',
        'lines' => [
            [
                'account_id' => $account1->id,
                'debit' => 100,
                'credit' => 0,
            ],
            [
                'account_id' => $account2->id,
                'debit' => 0,
                'credit' => 100,
            ],
        ],
    ];

    $request = new StoreJournalEntryRequest();
    
    // We need to inject the data into the request instance for 'after' hooks to work properly
    // or simulate a full request, but for unit test simpler to pass data to validator.
    // However, the `withValidator` method accesses `$this->input()`.
    // So we need to mock the request input.
    
    $request->merge($data);
    $validator = Validator::make($data, $request->rules());
    
    // Manually call the withValidator to attach the after hook
    $request->withValidator($validator);

    expect($validator->passes())->toBeTrue();
});

test('it fails validation when debit and credit are unbalanced', function () {
    $account1 = Account::factory()->create();
    $account2 = Account::factory()->create();

    $data = [
        'entry_date' => '2023-01-01',
        'description' => 'Unbalanced Entry',
        'lines' => [
            [
                'account_id' => $account1->id,
                'debit' => 100,
                'credit' => 0,
            ],
            [
                'account_id' => $account2->id,
                'debit' => 0,
                'credit' => 50, // Unbalanced
            ],
        ],
    ];

    $request = new StoreJournalEntryRequest();
    $request->merge($data);
    $validator = Validator::make($data, $request->rules());
    $request->withValidator($validator);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('lines'))->toBe('Total Debit and Total Credit must be equal.');
});
