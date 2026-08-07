<?php

use Illuminate\Contracts\Validation\Validator;
use Tests\Unit\Requests\Concerns\ValidatesAllocationOverflowHarness;

uses()->group('validates-allocation-overflow');

function mockValidator(): array
{
    $errors = Mockery::mock();
    $validator = Mockery::mock(Validator::class);
    $validator->shouldReceive('errors')->andReturn($errors);

    return [$validator, $errors];
}

test('does not add error when allocated_amount is within max', function () {
    [$validator, $errors] = mockValidator();
    $errors->shouldReceive('add')->never();

    $harness = new ValidatesAllocationOverflowHarness([
        'allocations' => [
            ['invoice_id' => 1, 'allocated_amount' => 100],
        ],
    ]);

    $harness->runValidateAllocationOverflow(
        $validator,
        'allocations',
        'invoice_id',
        'Exceeds maximum',
        fn (int $id) => 200.0,
    );
});

test('does not add error when allocated_amount equals max', function () {
    [$validator, $errors] = mockValidator();
    $errors->shouldReceive('add')->never();

    $harness = new ValidatesAllocationOverflowHarness([
        'allocations' => [
            ['invoice_id' => 1, 'allocated_amount' => 150],
        ],
    ]);

    $harness->runValidateAllocationOverflow(
        $validator,
        'allocations',
        'invoice_id',
        'Exceeds maximum',
        fn (int $id) => 150.0,
    );
});

test('adds error when allocated_amount exceeds max allocation', function () {
    [$validator, $errors] = mockValidator();
    $errors->shouldReceive('add')
        ->once()
        ->with(
            'allocations.0.allocated_amount',
            'Exceeds maximum: 50',
        );

    $harness = new ValidatesAllocationOverflowHarness([
        'allocations' => [
            ['invoice_id' => 1, 'allocated_amount' => 100],
        ],
    ]);

    $harness->runValidateAllocationOverflow(
        $validator,
        'allocations',
        'invoice_id',
        'Exceeds maximum',
        fn (int $id) => 50.0,
    );
});

test('skips allocation with empty reference id', function () {
    [$validator, $errors] = mockValidator();
    $errors->shouldReceive('add')->never();

    $harness = new ValidatesAllocationOverflowHarness([
        'allocations' => [
            ['invoice_id' => '', 'allocated_amount' => 999],
        ],
    ]);

    $harness->runValidateAllocationOverflow(
        $validator,
        'allocations',
        'invoice_id',
        'Exceeds maximum',
        fn (int $id) => 0.0,
    );
});

test('skips allocation with missing allocated_amount', function () {
    [$validator, $errors] = mockValidator();
    $errors->shouldReceive('add')->never();

    $harness = new ValidatesAllocationOverflowHarness([
        'allocations' => [
            ['invoice_id' => 1, 'allocated_amount' => ''],
        ],
    ]);

    $harness->runValidateAllocationOverflow(
        $validator,
        'allocations',
        'invoice_id',
        'Exceeds maximum',
        fn (int $id) => 0.0,
    );
});

test('skips allocation when max allocation is null', function () {
    [$validator, $errors] = mockValidator();
    $errors->shouldReceive('add')->never();

    $harness = new ValidatesAllocationOverflowHarness([
        'allocations' => [
            ['invoice_id' => 1, 'allocated_amount' => 500],
        ],
    ]);

    $harness->runValidateAllocationOverflow(
        $validator,
        'allocations',
        'invoice_id',
        'Exceeds maximum',
        fn (int $id) => null,
    );
});

test('validates multiple allocations independently', function () {
    [$validator, $errors] = mockValidator();
    $errors->shouldReceive('add')
        ->once()
        ->with(
            'allocations.1.allocated_amount',
            'Limit: 25',
        );

    $harness = new ValidatesAllocationOverflowHarness([
        'allocations' => [
            ['invoice_id' => 1, 'allocated_amount' => 50],
            ['invoice_id' => 2, 'allocated_amount' => 100],
        ],
    ]);

    $harness->runValidateAllocationOverflow(
        $validator,
        'allocations',
        'invoice_id',
        'Limit',
        fn (int $id) => match ($id) {
            1 => 100.0,
            2 => 25.0,
            default => null,
        },
    );
});

test('uses custom input key and reference id key', function () {
    [$validator, $errors] = mockValidator();
    $errors->shouldReceive('add')
        ->once()
        ->with(
            'items.0.allocated_amount',
            'Over limit: 10',
        );

    $harness = new ValidatesAllocationOverflowHarness([
        'items' => [
            ['ref_id' => 5, 'allocated_amount' => 20],
        ],
    ]);

    $harness->runValidateAllocationOverflow(
        $validator,
        'items',
        'ref_id',
        'Over limit',
        fn (int $id) => 10.0,
    );
});

test('empty allocations array does not trigger errors', function () {
    [$validator, $errors] = mockValidator();
    $errors->shouldReceive('add')->never();

    $harness = new ValidatesAllocationOverflowHarness([
        'allocations' => [],
    ]);

    $harness->runValidateAllocationOverflow(
        $validator,
        'allocations',
        'invoice_id',
        'Exceeds maximum',
        fn (int $id) => 0.0,
    );
});
