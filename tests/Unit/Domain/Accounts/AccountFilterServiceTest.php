<?php

namespace Tests\Unit\Domain\Accounts;

use App\Domain\Accounts\AccountFilterService;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountFilterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filterService = new AccountFilterService();
    }

    public function test_it_can_filter_by_type()
    {
        Account::factory()->create(['type' => 'asset']);
        Account::factory()->create(['type' => 'liability']);

        $query = Account::query();
        $this->filterService->applyAdvancedFilters($query, ['type' => 'asset']);
        
        $this->assertEquals(1, $query->count());
        $this->assertEquals('asset', $query->first()->type);
    }

    public function test_it_can_filter_by_active_status()
    {
        Account::factory()->create(['is_active' => true]);
        Account::factory()->create(['is_active' => false]);

        $query = Account::query();
        $this->filterService->applyAdvancedFilters($query, ['is_active' => true]);
        
        $this->assertEquals(1, $query->count());
        $this->assertTrue((bool)$query->first()->is_active);
    }

    public function test_it_can_search_by_code_or_name()
    {
        Account::factory()->create(['code' => '111', 'name' => 'Cash']);
        Account::factory()->create(['code' => '222', 'name' => 'Supplies']);

        $query = Account::query();
        $this->filterService->applySearch($query, 'Cash', ['code', 'name']);
        $this->assertEquals(1, $query->count());

        $query = Account::query();
        $this->filterService->applySearch($query, '222', ['code', 'name']);
        $this->assertEquals(1, $query->count());
    }
}
