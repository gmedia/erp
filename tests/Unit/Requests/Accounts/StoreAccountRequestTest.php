<?php

namespace Tests\Unit\Requests\Accounts;

use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreAccountRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_validates_required_fields()
    {
        $request = new StoreAccountRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('coa_version_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_it_passes_with_valid_data()
    {
        $coaVersion = CoaVersion::factory()->create();
        $data = [
            'coa_version_id' => $coaVersion->id,
            'code' => '11000',
            'name' => 'Cash',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'level' => 1,
            'is_active' => true,
        ];

        $request = new StoreAccountRequest();
        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }
}
