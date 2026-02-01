<?php

use App\Http\Resources\CoaVersions\CoaVersionResource;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('coa-versions');

test('resource returns correct fields', function () {
    $coaVersion = CoaVersion::factory()->create([
        'name' => 'Version 1',
        'status' => 'active',
    ]);

    $resource = new CoaVersionResource($coaVersion);
    $data = $resource->toArray(request());

    expect($data)->toHaveKeys(['id', 'name', 'fiscal_year_id', 'fiscal_year', 'status', 'created_at', 'updated_at'])
        ->and($data['name'])->toBe('Version 1')
        ->and($data['status'])->toBe('active')
        ->and($data['fiscal_year'])->toHaveKeys(['id', 'name']);
});
