<?php

use App\Http\Resources\ApprovalFlows\ApprovalFlowCollection;
use App\Models\ApprovalFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('approval-flows');

test('collection transforms paginated approval flows correctly', function () {
    $flows = ApprovalFlow::factory()->count(3)->create();

    $paginator = new LengthAwarePaginator(
        $flows,
        3,
        15,
        1
    );

    $request = request();
    $collection = (new ApprovalFlowCollection($paginator))->toArray($request);

    $data = $collection['data'] ?? $collection;

    expect(collect($data))->toBeIterable()
        ->and(collect($data))->toHaveCount(3);
        
    $firstItem = collect($data)->first();
    
    expect($firstItem)
        ->toBeInstanceOf(\App\Http\Resources\ApprovalFlows\ApprovalFlowResource::class);
});
