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
            DemoMasterData::BRANCH_HEAD_OFFICE,
            'Jakarta Selatan',
            'Bandung',
            'Surabaya',
        ];

        $legacyNames = [
            DemoMasterData::LEGACY_BRANCH_HEAD_OFFICE => DemoMasterData::BRANCH_HEAD_OFFICE,
            'Branch 1' => 'Jakarta Selatan',
            'Branch 2' => 'Bandung',
            'Branch 3' => 'Surabaya',
        ];

        foreach ($legacyNames as $legacy => $replacement) {
            $branch = Branch::query()->where('name', $legacy)->first();

            if ($branch === null) {
                continue;
            }

            // Baris lama di-rename, bukan dihapus: banyak tabel mereferensi branch_id.
            // Bila nama baru sudah ada (ganda), baris lama dipertahankan apa adanya.
            if (Branch::query()->where('name', $replacement)->exists()) {
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
