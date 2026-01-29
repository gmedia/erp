<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class ProductCategoryControllerTest extends TestCase
{
    use RefreshDatabase, SimpleCrudTestTrait;

    protected $modelClass = ProductCategory::class;
    protected $endpoint = '/api/product-categories';
    protected $permissionPrefix = 'product_category';
    protected $structure = ['id', 'name', 'description', 'created_at', 'updated_at'];
}
