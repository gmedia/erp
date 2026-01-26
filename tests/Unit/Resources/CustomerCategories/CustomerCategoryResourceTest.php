<?php

namespace Tests\Unit\Resources\CustomerCategories;

use App\Http\Resources\CustomerCategories\CustomerCategoryResource;
use App\Models\CustomerCategory;
use Illuminate\Http\Request;
use Tests\TestCase;

class CustomerCategoryResourceTest extends TestCase
{
    public function test_it_returns_correct_array_structure(): void
    {
        $category = new CustomerCategory();
        $category->id = 1;
        $category->name = 'Test Category';
        $category->created_at = now();
        $category->updated_at = now();

        $resource = new CustomerCategoryResource($category);
        $result = $resource->toArray(new Request());

        $this->assertEquals([
            'id' => 1,
            'name' => 'Test Category',
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ], $result);
    }
}
