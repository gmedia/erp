<?php

namespace Tests\Unit\Actions\Accounts;

use App\Actions\Accounts\ExportAccountsAction;
use App\Domain\Accounts\AccountFilterService;
use App\Http\Requests\Accounts\ExportAccountRequest;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportAccountsActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filterService = new AccountFilterService();
        $this->action = new ExportAccountsAction($this->filterService);
    }

    public function test_it_returns_json_response_with_filters()
    {
        $coaVersion = CoaVersion::factory()->create();
        $request = new ExportAccountRequest([
            'coa_version_id' => $coaVersion->id,
            'search' => 'test'
        ]);

        $response = $this->action->execute($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Export functionality would be implemented here.', $data['message']);
        $this->assertEquals('test', $data['filters']['search']);
    }
}
