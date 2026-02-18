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
    }
}

