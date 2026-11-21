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
            'Head Office',
            'Jakarta Selatan',
            'Bandung',
            'Surabaya',
        ];

        $legacyNames = [
            'Branch 1' => 'Jakarta Selatan',
            'Branch 2' => 'Bandung',
            'Branch 3' => 'Surabaya',
        ];

        foreach ($legacyNames as $legacy => $replacement) {
            $branch = Branch::query()->where('name', $legacy)->first();

            if ($branch === null) {
                continue;
            }

            if (Branch::query()->where('name', $replacement)->exists()) {
                $branch->delete();

                continue;
            }

            $branch->update(['name' => $replacement]);
        }

        foreach ($branches as $name) {
            Branch::updateOrCreate(
                ['name' => $name],
                ['company_id' => $company->id],
            );
        }
    }
}
