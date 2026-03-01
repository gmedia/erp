<?php

namespace Tests\Unit\Requests\ApprovalDelegations;

use App\Http\Requests\ApprovalDelegations\UpdateApprovalDelegationRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateApprovalDelegationRequestTest extends TestCase
{
    private UpdateApprovalDelegationRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new UpdateApprovalDelegationRequest();
    }

    public function test_it_allows_valid_data(): void
    {
        $validator = Validator::make([
            'reason' => 'Updated reason',
            'is_active' => false,
        ], $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_it_allows_almost_all_fields_to_be_updated(): void
    {
        // delegate_user_id, start_date, end_date can also be updated
        $validator = Validator::make([
            'delegate_user_id' => 2,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-10',
            'is_active' => true,
        ], $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_it_validates_end_date_after_start_date_when_both_provided(): void
    {
        $validator = Validator::make([
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-01', // Invalid
        ], $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('end_date', $validator->errors()->messages());
    }
}
