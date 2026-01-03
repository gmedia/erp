<?php

use App\Actions\Positions\CreatePositionAction;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

test('execute creates a new position', function () {
    $action = new CreatePositionAction();

    $data = [
        'name' => 'Software Engineer',
    ];

    $position = $action->execute($data);

    expect($position)->toBeInstanceOf(Position::class)
        ->and($position->name)->toBe('Software Engineer');

    assertDatabaseHas('positions', ['name' => 'Software Engineer']);
});

test('execute creates position with additional data', function () {
    $action = new CreatePositionAction();

    $data = [
        'name' => 'Senior Developer',
        'description' => 'Senior software development position',
    ];

    $position = $action->execute($data);

    expect($position->name)->toBe('Senior Developer')
        ->and($position->description)->toBe('Senior software development position');
});
