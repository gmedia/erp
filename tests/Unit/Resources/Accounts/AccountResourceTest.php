<?php

namespace Tests\Unit\Resources\Accounts;

use App\Http\Resources\Accounts\AccountResource;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_formats_account_data()
    {
        $account = Account::factory()->create([
            'code' => '11000',
            'name' => 'Cash',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $resource = new AccountResource($account);
        $data = $resource->toArray(request());

        $this->assertEquals($account->id, $data['id']);
        $this->assertEquals('11000', $data['code']);
        $this->assertEquals('Cash', $data['name']);
        $this->assertEquals('asset', $data['type']);
        $this->assertEquals('debit', $data['normal_balance']);
        $this->assertTrue((bool)$data['is_active']);
    }
}
