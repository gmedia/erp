<?php

namespace Tests\Unit\Requests\CustomerCategories;

use App\Http\Requests\CustomerCategories\StoreCustomerCategoryRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreCustomerCategoryRequestTest extends TestCase
{
    public function test_validation_fails_if_name_is_missing(): void
    {
        $request = new StoreCustomerCategoryRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $request = new StoreCustomerCategoryRequest();
        $validator = Validator::make(['name' => 'New Category'], $request->rules());

        $this->assertFalse($validator->fails());
    }
}
