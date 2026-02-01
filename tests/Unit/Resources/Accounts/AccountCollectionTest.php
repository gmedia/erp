<?php

namespace Tests\Unit\Resources\Accounts;

use App\Http\Resources\Accounts\AccountCollection;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_collection_of_resources()
    {
        $coaVersion = CoaVersion::factory()->create();
        
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => 'ACC001',
            'name' => 'Account 1',
        ]);
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => 'ACC002',
            'name' => 'Account 2',
        ]);
        Account::factory()->create([
            'coa_version_id' => $coaVersion->id,
            'code' => 'ACC003',
            'name' => 'Account 3',
        ]);
        
        $accounts = Account::all();

        $collection = new AccountCollection($accounts);
        $data = $collection->toArray(request());

        // The collection has 'data' and 'meta' keys
        $this->assertCount(3, $data['data']);
        $this->assertEquals(3, $data['meta']['count']);
        $this->assertArrayHasKey('id', $data['data'][0]);
    }
}
