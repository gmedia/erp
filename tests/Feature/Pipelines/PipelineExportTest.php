<?php

namespace Tests\Feature\Pipelines;

use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('pipelines');

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can export pipelines', function () {
    Pipeline::factory()->count(2)->create();

    $response = $this->actingAs($this->user)->postJson('/api/pipelines/export');

    $response->assertStatus(200)
        ->assertJsonStructure(['url', 'filename']);
});
