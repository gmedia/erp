<?php

namespace Tests\Feature;

use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class SupplierCategoryControllerTest extends TestCase
{
    use RefreshDatabase;
    use SimpleCrudTestTrait;

    protected $modelClass = SupplierCategory::class;
    protected $endpoint = '/api/supplier-categories';
    protected $permissionPrefix = 'supplier_category';
    protected $structure = ['id', 'name', 'created_at', 'updated_at'];
}
