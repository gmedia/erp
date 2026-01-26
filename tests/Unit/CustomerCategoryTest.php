<?php

namespace Tests\Unit;

use App\Models\CustomerCategory;
use Tests\TestCase;

class CustomerCategoryTest extends TestCase
{
    public function test_it_has_fillable_attributes(): void
    {
        $category = new CustomerCategory();
        $this->assertEquals(['name'], $category->getFillable());
    }
}
