<?php

namespace Tests\Unit\Actions\Accounts;

use App\Actions\Accounts\IndexAccountsAction;
use App\Domain\Accounts\AccountFilterService;
use App\Http\Requests\Accounts\IndexAccountRequest;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAccountsActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filterService = new AccountFilterService();
        $this->action = new IndexAccountsAction($this->filterService);
    }

    public function test_it_can_index_accounts_by_version()
    {
        $coaVersion = CoaVersion::factory()->create();
        Account::factory()->count(5)->create(['coa_version_id' => $coaVersion->id]);
        Account::factory()->count(3)->create(); // Other versions

        $request = new IndexAccountRequest(['coa_version_id' => $coaVersion->id]);
        
        $result = $this->action->execute($request);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
    }

    public function test_it_can_paginate_accounts()
    {
        $coaVersion = CoaVersion::factory()->create();
        Account::factory()->count(20)->create(['coa_version_id' => $coaVersion->id]);

        $request = new IndexAccountRequest([
            'coa_version_id' => $coaVersion->id,
            'per_page' => 10
        ]);
        
        $result = $this->action->execute($request);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(20, $result->total());
        $this->assertCount(10, $result->items());
    }
}
