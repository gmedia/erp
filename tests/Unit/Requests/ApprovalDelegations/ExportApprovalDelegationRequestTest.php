<?php

namespace Tests\Unit\Requests\ApprovalDelegations;

use App\Http\Requests\ApprovalDelegations\ExportApprovalDelegationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportApprovalDelegationRequestTest extends TestCase
{
    private ExportApprovalDelegationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ExportApprovalDelegationRequest();
    }

    public function test_it_allows_valid_data(): void
    {
        $validator = Validator::make([
            'search' => 'test',
            'is_active' => 'true',
            'start_date_from' => '2026-01-01',
            'start_date_to' => '2026-01-31',
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ], $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_it_rejects_invalid_sort_by(): void
    {
        $validator = Validator::make([
            'sort_by' => 'invalid_column',
        ], $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('sort_by', $validator->errors()->messages());
    }
}
