<?php

namespace Tests\Unit\Requests\Accounts;

use App\Http\Requests\Accounts\ExportAccountRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ExportAccountRequestTest extends TestCase
{
    public function test_it_validates_required_coa_version_id()
    {
        $request = new ExportAccountRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('coa_version_id', $validator->errors()->toArray());
    }

    public function test_it_validates_type_if_provided()
    {
        $request = new ExportAccountRequest();
        $validator = Validator::make([
            'coa_version_id' => 1,
            'type' => 'invalid'
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }
}
