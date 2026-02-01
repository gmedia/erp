<?php

use App\Http\Resources\CoaVersions\CoaVersionCollection;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('coa-versions');

test('collection returns correct structure', function () {
    CoaVersion::factory()->count(3)->create();

    $resource = new CoaVersionCollection(CoaVersion::paginate());
    $response = $resource->toResponse(request());
    $data = $response->getData(true);

    expect($data)->toHaveKeys(['data', 'links', 'meta'])
        ->and($data['data'])->toHaveCount(3);
});
