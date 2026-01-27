<?php

namespace Tests\Feature;

use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class CustomerCategoryControllerTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudTestTrait;

    protected $modelClass = CustomerCategory::class;
    protected $endpoint = '/api/customer-categories';
    protected $permissionPrefix = 'customer_category';
    protected $structure = ['id', 'name', 'created_at', 'updated_at'];
}
