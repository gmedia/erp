<?php

namespace Tests\Unit\Requests\Accounts;

use App\Http\Requests\Accounts\IndexAccountRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexAccountRequestTest extends TestCase
{
    public function test_it_validates_coa_version_id_is_required_if_present()
    {
        $request = new IndexAccountRequest();
        // Since it's usually passed via query, and we need to check if it's integer
        $validator = Validator::make(['coa_version_id' => 'abc'], $request->rules());
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('coa_version_id', $validator->errors()->toArray());
    }

    public function test_it_allows_null_or_empty_search()
    {
        $request = new IndexAccountRequest();
        $coaVersion = \App\Models\CoaVersion::factory()->create();
        $validator = Validator::make(['coa_version_id' => $coaVersion->id, 'search' => ''], $request->rules());
        $this->assertTrue($validator->passes());
    }
}
