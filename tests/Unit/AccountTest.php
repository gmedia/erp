<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_fillable_attributes()
    {
        $data = [
            'coa_version_id' => 1,
            'parent_id' => null,
            'code' => '11000',
            'name' => 'Cash',
            'type' => 'asset',
            'sub_type' => null,
            'normal_balance' => 'debit',
            'level' => 1,
            'is_active' => true,
            'is_cash_flow' => false,
            'description' => 'Business cash',
        ];

        $account = new Account($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $account->$key);
        }
    }

    public function test_it_belongs_to_coa_version()
    {
        $coaVersion = CoaVersion::factory()->create();
        $account = Account::factory()->create(['coa_version_id' => $coaVersion->id]);

        $this->assertInstanceOf(CoaVersion::class, $account->coaVersion);
        $this->assertEquals($coaVersion->id, $account->coaVersion->id);
    }

    public function test_it_can_have_parent_and_children()
    {
        $parent = Account::factory()->create();
        $child = Account::factory()->create(['parent_id' => $parent->id]);

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains($child));
    }
}
