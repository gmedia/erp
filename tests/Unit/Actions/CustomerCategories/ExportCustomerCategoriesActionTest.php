<?php

namespace Tests\Unit\Actions\CustomerCategories;

use App\Actions\CustomerCategories\ExportCustomerCategoriesAction;
use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExportCustomerCategoriesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_returns_download_url(): void
    {
        Storage::fake('public');
        Excel::fake();

        $action = new ExportCustomerCategoriesAction(new CustomerCategoryFilterService());
        
        $request = new ExportCustomerCategoryRequest();
        $request->setContainer(app());
        $request->setValidator(app('validator')->make([], $request->rules()));

        $response = $action->execute($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('url', $response->getData(true));
    }
}
