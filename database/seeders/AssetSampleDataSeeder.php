<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\AssetModel;
use App\Models\AssetMovement;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', config('app.admin'))->first();
        $adminEmployee = Employee::where('email', config('app.admin'))->first();
        $defaultDepartment = Department::query()->orderBy('id')->first();

        $supplier = Supplier::query()->orderBy('id')->first();
        if (! $supplier) {
            $category = SupplierCategory::query()->orderBy('id')->first()
                ?? SupplierCategory::create(['name' => 'General']);

            $branch = Branch::query()->orderBy('id')->first() ?? Branch::create(['name' => 'Head Office']);

            $supplier = Supplier::create([
                'name' => 'Default Supplier',
                'email' => 'supplier@example.com',
                'phone' => '0800000000',
                'address' => 'Head Office',
                'branch_id' => $branch->id,
                'category_id' => $category->id,
                'status' => 'active',
            ]);
        }

        // Check for existing categories from AssetCategorySeeder first
        $itCategory = AssetCategory::where('code', 'IT')->first();
        $vehCategory = AssetCategory::where('code', 'KND')->first();
        $offCategory = AssetCategory::where('code', 'PRB')->first();
        $mchCategory = AssetCategory::where('code', 'MSN')->first();

        // Fallback or verify they exist. 
        // Since AssetCategorySeeder runs first, they should exist.
        // If they don't we could create them, but better to rely on AssetCategorySeeder.

        $models = [
            [
                'asset_category_id' => $itCategory?->id,
                'manufacturer' => 'Dell',
                'model_name' => 'Laptop Latitude 5430',
                'specs' => ['cpu' => 'i5', 'ram_gb' => 16, 'storage_gb' => 512],
            ],
            [
                'asset_category_id' => $itCategory?->id,
                'manufacturer' => 'HP',
                'model_name' => 'Printer LaserJet Pro',
                'specs' => ['type' => 'printer', 'connection' => 'network'],
            ],
            [
                'asset_category_id' => $vehCategory?->id,
                'manufacturer' => 'Toyota',
                'model_name' => 'Avanza 1.5 G',
                'specs' => ['fuel' => 'gasoline'],
            ],
            [
                'asset_category_id' => $offCategory?->id,
                'manufacturer' => 'IKEA',
                'model_name' => 'Office Desk Standard',
                'specs' => ['material' => 'wood'],
            ],
            [
                'asset_category_id' => $mchCategory?->id,
                'manufacturer' => 'Generic',
                'model_name' => 'Packing Machine',
                'specs' => ['power' => '220V'],
            ],
        ];

        foreach ($models as $data) {
            if (! $data['asset_category_id']) {
                continue;
            }

            AssetModel::updateOrCreate(
                ['asset_category_id' => $data['asset_category_id'], 'model_name' => $data['model_name']],
                $data
            );
        }

        $branches = Branch::query()->orderBy('id')->get();
        foreach ($branches as $branch) {
            $office = AssetLocation::updateOrCreate(
                ['branch_id' => $branch->id, 'code' => 'OFFICE'],
                ['name' => 'Office ('.$branch->name.')', 'parent_id' => null]
            );

            AssetLocation::updateOrCreate(
                ['branch_id' => $branch->id, 'code' => 'IT-ROOM'],
                ['name' => 'IT Room ('.$branch->name.')', 'parent_id' => $office->id]
            );

            AssetLocation::updateOrCreate(
                ['branch_id' => $branch->id, 'code' => 'WAREHOUSE'],
                ['name' => 'Warehouse ('.$branch->name.')', 'parent_id' => null]
            );
        }

        $headOffice = $branches->first();
        if (! $headOffice) {
            return;
        }

        $itRoom = AssetLocation::where('branch_id', $headOffice->id)->where('code', 'IT-ROOM')->first();

        $laptopModel = AssetModel::where('model_name', 'Laptop Latitude 5430')->first();
        $printerModel = AssetModel::where('model_name', 'Printer LaserJet Pro')->first();
        $carModel = AssetModel::where('model_name', 'Avanza 1.5 G')->first();

        $assets = [
            [
                'asset_code' => 'FA-000001',
                'name' => 'Laptop Admin',
                'asset_model_id' => $laptopModel?->id,
                'asset_category_id' => $itCategory?->id,
                'serial_number' => 'SN-000001',
                'branch_id' => $headOffice->id,
                'asset_location_id' => $itRoom?->id,
                'department_id' => $defaultDepartment?->id,
                'employee_id' => $adminEmployee?->id,
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->subMonths(10)->toDateString(),
                'purchase_cost' => 18000000,
                'currency' => 'IDR',
                'warranty_end_date' => now()->addMonths(14)->toDateString(),
                'status' => 'active',
                'condition' => 'good',
                'depreciation_method' => 'straight_line',
                'depreciation_start_date' => now()->subMonths(10)->toDateString(),
                'useful_life_months' => 36,
                'salvage_value' => 0,
                'accumulated_depreciation' => 0,
                'book_value' => 18000000,
            ],
            [
                'asset_code' => 'FA-000002',
                'name' => 'Printer Office',
                'asset_model_id' => $printerModel?->id,
                'asset_category_id' => $itCategory?->id,
                'serial_number' => 'SN-000002',
                'branch_id' => $headOffice->id,
                'asset_location_id' => $itRoom?->id,
                'department_id' => $defaultDepartment?->id,
                'employee_id' => null,
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->subMonths(6)->toDateString(),
                'purchase_cost' => 4500000,
                'currency' => 'IDR',
                'warranty_end_date' => now()->addMonths(18)->toDateString(),
                'status' => 'active',
                'condition' => 'good',
                'depreciation_method' => 'straight_line',
                'depreciation_start_date' => now()->subMonths(6)->toDateString(),
                'useful_life_months' => 36,
                'salvage_value' => 0,
                'accumulated_depreciation' => 0,
                'book_value' => 4500000,
            ],
            [
                'asset_code' => 'FA-000003',
                'name' => 'Company Car',
                'asset_model_id' => $carModel?->id,
                'asset_category_id' => $vehCategory?->id,
                'serial_number' => 'SN-000003',
                'branch_id' => $headOffice->id,
                'asset_location_id' => null,
                'department_id' => $defaultDepartment?->id,
                'employee_id' => $adminEmployee?->id,
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->subMonths(20)->toDateString(),
                'purchase_cost' => 260000000,
                'currency' => 'IDR',
                'warranty_end_date' => null,
                'status' => 'active',
                'condition' => 'good',
                'depreciation_method' => 'straight_line',
                'depreciation_start_date' => now()->subMonths(20)->toDateString(),
                'useful_life_months' => 60,
                'salvage_value' => 0,
                'accumulated_depreciation' => 0,
                'book_value' => 260000000,
            ],
        ];

        foreach ($assets as $data) {
            $asset = Asset::updateOrCreate(['asset_code' => $data['asset_code']], $data);

            AssetMovement::updateOrCreate(
                ['asset_id' => $asset->id, 'movement_type' => 'acquired', 'moved_at' => $asset->purchase_date],
                [
                    'from_branch_id' => null,
                    'to_branch_id' => $asset->branch_id,
                    'from_location_id' => null,
                    'to_location_id' => $asset->asset_location_id,
                    'from_department_id' => null,
                    'to_department_id' => $asset->department_id,
                    'from_employee_id' => null,
                    'to_employee_id' => $asset->employee_id,
                    'reference' => 'INIT',
                    'notes' => null,
                    'created_by' => $adminUser?->id,
                ]
            );
        }

        $laptopAsset = Asset::where('asset_code', 'FA-000001')->first();
        $printerAsset = Asset::where('asset_code', 'FA-000002')->first();
        $carAsset = Asset::where('asset_code', 'FA-000003')->first();
        $warehouse = AssetLocation::where('branch_id', $headOffice->id)->where('code', 'WAREHOUSE')->first();

        // 1. Asset Movement (Transfer Printer)
        if ($printerAsset && $warehouse && $itRoom && $defaultDepartment) {
            if ($printerAsset->asset_location_id !== $warehouse->id) {
                AssetMovement::create([
                    'asset_id' => $printerAsset->id,
                    'movement_type' => 'transfer',
                    'moved_at' => now()->subMonths(2)->toDateString(),
                    'from_branch_id' => $headOffice->id,
                    'to_branch_id' => $headOffice->id,
                    'from_location_id' => $itRoom->id,
                    'to_location_id' => $warehouse->id,
                    'from_department_id' => $defaultDepartment->id,
                    'to_department_id' => $defaultDepartment->id,
                    'reference' => 'TRF-001',
                    'created_by' => $adminUser?->id,
                ]);
                $printerAsset->update(['asset_location_id' => $warehouse->id]);
            }
        }

        // 2. Asset Maintenance
        if ($laptopAsset && ! \App\Models\AssetMaintenance::where('asset_id', $laptopAsset->id)->exists()) {
            \App\Models\AssetMaintenance::create([
                'asset_id' => $laptopAsset->id,
                'supplier_id' => $supplier->id,
                'maintenance_type' => 'corrective',
                'notes' => 'Screen Replacement - Replaced broken LCD screen.',
                'scheduled_at' => now()->subMonths(3)->toDateString(),
                'performed_at' => now()->subMonths(3)->addDays(2)->toDateString(),
                'cost' => 1500000,
                'status' => 'completed',
                'created_by' => $adminUser?->id,
            ]);
        }

        if ($carAsset && ! \App\Models\AssetMaintenance::where('asset_id', $carAsset->id)->exists()) {
            \App\Models\AssetMaintenance::create([
                'asset_id' => $carAsset->id,
                'supplier_id' => $supplier->id,
                'maintenance_type' => 'preventive',
                'notes' => 'Routine Service (10,000 KM) - Oil change and general checkup.',
                'scheduled_at' => now()->addDays(5)->toDateString(),
                'status' => 'scheduled',
                'created_by' => $adminUser?->id,
            ]);
        }

        // 3. Asset Stocktake & Items Check
        if (! \App\Models\AssetStocktake::where('branch_id', $headOffice->id)->exists()) {
            $stocktake = \App\Models\AssetStocktake::create([
                'branch_id' => $headOffice->id,
                'reference' => 'STK-' . date('Ym'),
                'planned_at' => now()->subDays(1),
                'performed_at' => now(),
                'status' => 'completed',
                'created_by' => $adminUser?->id,
            ]);

            if ($laptopAsset) {
                \App\Models\AssetStocktakeItem::create([
                    'asset_stocktake_id' => $stocktake->id,
                    'asset_id' => $laptopAsset->id,
                    'expected_branch_id' => $headOffice->id,
                    'expected_location_id' => $itRoom?->id,
                    'found_branch_id' => $headOffice->id,
                    'found_location_id' => $itRoom?->id,
                    'result' => 'found',
                    'checked_at' => now(),
                    'checked_by' => $adminUser?->id,
                ]);
            }

            if ($printerAsset) {
                \App\Models\AssetStocktakeItem::create([
                    'asset_stocktake_id' => $stocktake->id,
                    'asset_id' => $printerAsset->id,
                    'expected_branch_id' => $headOffice->id,
                    'expected_location_id' => $warehouse?->id,
                    'result' => 'missing',
                    'checked_at' => now(),
                    'checked_by' => $adminUser?->id,
                ]);
            }

            if ($carAsset) {
                \App\Models\AssetStocktakeItem::create([
                    'asset_stocktake_id' => $stocktake->id,
                    'asset_id' => $carAsset->id,
                    'expected_branch_id' => $headOffice->id,
                    'expected_location_id' => null,
                    'found_branch_id' => $headOffice->id,
                    'found_location_id' => null,
                    'result' => 'found',
                    'checked_at' => now(),
                    'checked_by' => $adminUser?->id,
                ]);
            }
        }

        // 4. Depreciation Run & Journal Post
        $fiscalYear = \App\Models\FiscalYear::where('status', 'open')->first();
        if ($fiscalYear) {
            $periodStart = now()->subMonth()->startOfMonth();
            $periodEnd = now()->subMonth()->endOfMonth();
            
            // Generate some random depreciation_expense_account_id if none exists to avoid validation error in PostToJournal
            $expenseAcc = \App\Models\Account::where('type', 'expense')->first();
            $accumAcc = \App\Models\Account::where('type', 'asset')->first(); // Normally "accumulated_depreciation" but using closest standard type if unavailable
            
            if ($expenseAcc && $accumAcc) {
                // Ensure all seeded assets have accounts set
                Asset::whereNull('depreciation_expense_account_id')->update([
                    'depreciation_expense_account_id' => $expenseAcc->id,
                    'accumulated_depr_account_id' => $accumAcc->id,
                ]);

                if (! \App\Models\AssetDepreciationRun::where('fiscal_year_id', $fiscalYear->id)->where('period_start', $periodStart->toDateString())->exists()) {
                    $calculateAction = app(\App\Actions\AssetDepreciationRuns\CalculateDepreciationAction::class);
                    $run = $calculateAction->execute([
                        'fiscal_year_id' => $fiscalYear->id,
                        'period_start' => $periodStart->toDateString(),
                        'period_end' => $periodEnd->toDateString(),
                    ]);

                    $postAction = app(\App\Actions\AssetDepreciationRuns\PostDepreciationToJournalAction::class);
                    $postAction->execute($run);
                }
            }
        }
    }
}

