<?php

use App\Http\Resources\Branches\BranchResource;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('branches');

test('to array returns correct structure', function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->create([
        'name' => 'Main Branch',
        'company_id' => $company->id,
    ]);

    $resource = new BranchResource($branch);
    $request = Request::create('/');

    $result = $resource->toArray($request);

    expect($result)->toMatchArray([
        'id' => $branch->id,
        'name' => 'Main Branch',
        'company_id' => $company->id,
    ]);

    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
