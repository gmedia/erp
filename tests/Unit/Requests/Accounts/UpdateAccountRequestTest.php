<?php

namespace Tests\Unit\Requests\Accounts;

use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateAccountRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_validates_required_fields()
    {
        $coaVersion = CoaVersion::factory()->create();
        $account = Account::factory()->create(['coa_version_id' => $coaVersion->id]);

        $request = new UpdateAccountRequest();
        
        // Simpler way to mock route parameter for unit test
        $request->merge(['coa_version_id' => $coaVersion->id]);
        $request->setRouteResolver(function () use ($account) {
            $mockRoute = $this->createMock(\Illuminate\Routing\Route::class);
            $mockRoute->method('parameter')->with('account')->willReturn($account);
            return $mockRoute;
        });

        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    public function test_it_passes_with_valid_data()
    {
        $coaVersion = CoaVersion::factory()->create();
        $account = Account::factory()->create(['coa_version_id' => $coaVersion->id]);
        
        $data = [
            'coa_version_id' => $coaVersion->id,
            'code' => '11001',
            'name' => 'Updated Cash',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'level' => 1,
            'is_active' => true,
            'is_cash_flow' => false,
        ];

        $request = new UpdateAccountRequest();
        $request->merge($data);
        $request->setRouteResolver(function() use ($account) {
            $mockRoute = $this->createMock(\Illuminate\Routing\Route::class);
            $mockRoute->method('parameter')->with('account')->willReturn($account);
            return $mockRoute;
        });

        $validator = Validator::make($data, $request->rules());

        $this->assertTrue($validator->passes());
    }
}
