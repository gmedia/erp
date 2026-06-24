<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('user-guide');

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);
});

test('index returns list of available user guides', function () {
    $response = getJson('/api/user-guide');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'slug',
                    'title',
                    'filename',
                ],
            ],
        ]);

    $slugs = collect($response->json('data'))->pluck('slug')->all();

    expect($slugs)
        ->toContain('accounts-payable')
        ->toContain('accounts-receivable')
        ->toContain('pipeline');
});

test('index sorts guides by title ascending', function () {
    $response = getJson('/api/user-guide')->assertOk();

    $titles = collect($response->json('data'))->pluck('title')->all();
    $sorted = $titles;
    asort($sorted, SORT_NATURAL | SORT_FLAG_CASE);

    expect($titles)->toBe($sorted);
});

test('show returns guide content with extracted title', function () {
    $response = getJson('/api/user-guide/accounts-payable');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['slug', 'title', 'content'],
        ])
        ->assertJsonPath('data.slug', 'accounts-payable');

    $payload = $response->json('data');
    expect($payload['title'])->toBe('User Guide: Accounts Payable (AP)');
    expect($payload['content'])->toStartWith('# User Guide: Accounts Payable (AP)');
});

test('show returns 404 for non-existent guide', function () {
    getJson('/api/user-guide/does-not-exist')
        ->assertStatus(404)
        ->assertJsonPath('message', 'Guide not found.');
});
