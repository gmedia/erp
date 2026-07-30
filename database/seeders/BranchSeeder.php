<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['name' => 'PT. Default Company'],
        );

        $branches = [
            'Head Office', 'Branch 1', 'Branch 2', 'Branch 3',
        ];

        foreach ($branches as $name) {
            Branch::updateOrCreate(
                ['name' => $name],
                ['company_id' => $company->id],
            );
        }
    }
}
