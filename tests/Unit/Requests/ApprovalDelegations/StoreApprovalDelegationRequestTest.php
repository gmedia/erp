<?php

namespace Tests\Unit\Requests\ApprovalDelegations;

use App\Http\Requests\ApprovalDelegations\StoreApprovalDelegationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreApprovalDelegationRequestTest extends TestCase
{
    private StoreApprovalDelegationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new StoreApprovalDelegationRequest();
    }

    public function test_it_allows_valid_data(): void
    {
        $validator = Validator::make([
            'delegator_user_id' => 1,
            'delegate_user_id' => 2,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
            'reason' => 'Test reason',
            'is_active' => true,
        ], $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_it_requires_all_mandatory_fields(): void
    {
        $validator = Validator::make([], $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('delegator_user_id', $validator->errors()->messages());
        $this->assertArrayHasKey('delegate_user_id', $validator->errors()->messages());
        $this->assertArrayHasKey('start_date', $validator->errors()->messages());
        $this->assertArrayHasKey('end_date', $validator->errors()->messages());
    }

    public function test_it_validates_delegate_cannot_be_delegator(): void
    {
        $validator = Validator::make([
            'delegator_user_id' => 1,
            'delegate_user_id' => 1, // Invalid
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
        ], $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('delegate_user_id', $validator->errors()->messages());
    }

    public function test_it_validates_end_date_after_or_equal_to_start_date(): void
    {
        $validator = Validator::make([
            'delegator_user_id' => 1,
            'delegate_user_id' => 2,
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-01', // Invalid
        ], $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('end_date', $validator->errors()->messages());
    }
}
