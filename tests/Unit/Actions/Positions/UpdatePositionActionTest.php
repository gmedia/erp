<?php

use App\Actions\Positions\UpdatePositionAction;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('execute updates position name', function () {
    $action = new UpdatePositionAction();

    $position = Position::factory()->create([
        'name' => 'Old Position',
    ]);

    $data = [
        'name' => 'New Position',
    ];

    $updatedPosition = $action->execute($position, $data);

    expect($updatedPosition->name)->toBe('New Position')
        ->and($updatedPosition->id)->toBe($position->id);

    $position->refresh();
    expect($position->name)->toBe('New Position');
});

test('execute updates multiple fields', function () {
    $action = new UpdatePositionAction();

    $position = Position::factory()->create([
        'name' => 'Developer',
        'description' => 'Old description',
    ]);

    $data = [
        'name' => 'Senior Developer',
        'description' => 'New description',
    ];

    $updatedPosition = $action->execute($position, $data);

    expect($updatedPosition->name)->toBe('Senior Developer')
        ->and($updatedPosition->description)->toBe('New description');
});

test('execute returns fresh model instance', function () {
    $action = new UpdatePositionAction();

    $position = Position::factory()->create();

    $data = [
        'name' => 'Updated Position',
    ];

    $updatedPosition = $action->execute($position, $data);

    expect($updatedPosition)->toBeInstanceOf(Position::class)
        ->and($updatedPosition)->not->toBe($position); // Different instance due to fresh()
});
