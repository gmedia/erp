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
use App\Models\Pipeline;
use App\Models\PipelineEntityState;
use App\Models\PipelineState;
use App\Models\PipelineStateLog;
use App\Models\PipelineTransition;
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

        // ── Asset Categories ────────────────────────────────────────
        $itCategory = AssetCategory::where('code', 'IT')->first();
        $vehCategory = AssetCategory::where('code', 'KND')->first();
        $offCategory = AssetCategory::where('code', 'PRB')->first();
        $mchCategory = AssetCategory::where('code', 'MSN')->first();

        // ── Asset Models ────────────────────────────────────────────
        $models = [
            ['asset_category_id' => $itCategory?->id, 'manufacturer' => 'Dell', 'model_name' => 'Laptop Latitude 5430', 'specs' => ['cpu' => 'i5', 'ram_gb' => 16, 'storage_gb' => 512]],
            ['asset_category_id' => $itCategory?->id, 'manufacturer' => 'HP', 'model_name' => 'Printer LaserJet Pro', 'specs' => ['type' => 'printer', 'connection' => 'network']],
            ['asset_category_id' => $vehCategory?->id, 'manufacturer' => 'Toyota', 'model_name' => 'Avanza 1.5 G', 'specs' => ['fuel' => 'gasoline']],
            ['asset_category_id' => $offCategory?->id, 'manufacturer' => 'IKEA', 'model_name' => 'Office Desk Standard', 'specs' => ['material' => 'wood']],
            ['asset_category_id' => $mchCategory?->id, 'manufacturer' => 'Generic', 'model_name' => 'Packing Machine', 'specs' => ['power' => '220V']],
            ['asset_category_id' => $itCategory?->id, 'manufacturer' => 'Lenovo', 'model_name' => 'ThinkPad X1 Carbon', 'specs' => ['cpu' => 'i7', 'ram_gb' => 16, 'storage_gb' => 256]],
            ['asset_category_id' => $itCategory?->id, 'manufacturer' => 'Epson', 'model_name' => 'Proyektor EB-X51', 'specs' => ['type' => 'projector', 'lumens' => 3800]],
            ['asset_category_id' => $offCategory?->id, 'manufacturer' => 'Krisbow', 'model_name' => 'Filing Cabinet 4 Drawer', 'specs' => ['material' => 'steel']],
        ];

        foreach ($models as $data) {
            if (! $data['asset_category_id']) continue;
            AssetModel::updateOrCreate(
                ['asset_category_id' => $data['asset_category_id'], 'model_name' => $data['model_name']],
                $data
            );
        }

        // ── Asset Locations ─────────────────────────────────────────
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
        if (! $headOffice) return;

        $itRoom = AssetLocation::where('branch_id', $headOffice->id)->where('code', 'IT-ROOM')->first();
        $office = AssetLocation::where('branch_id', $headOffice->id)->where('code', 'OFFICE')->first();
        $warehouse = AssetLocation::where('branch_id', $headOffice->id)->where('code', 'WAREHOUSE')->first();

        // ── Retrieve Models ─────────────────────────────────────────
        $laptopModel    = AssetModel::where('model_name', 'Laptop Latitude 5430')->first();
        $printerModel   = AssetModel::where('model_name', 'Printer LaserJet Pro')->first();
        $carModel       = AssetModel::where('model_name', 'Avanza 1.5 G')->first();
        $deskModel      = AssetModel::where('model_name', 'Office Desk Standard')->first();
        $machineModel   = AssetModel::where('model_name', 'Packing Machine')->first();
        $thinkpadModel  = AssetModel::where('model_name', 'ThinkPad X1 Carbon')->first();
        $projectorModel = AssetModel::where('model_name', 'Proyektor EB-X51')->first();
        $cabinetModel   = AssetModel::where('model_name', 'Filing Cabinet 4 Drawer')->first();

        // ══════════════════════════════════════════════════════════════
        // SAMPLE ASSETS — One or more per pipeline state
        // ══════════════════════════════════════════════════════════════
        //
        // Pipeline states:
        //   draft       → Baru didaftarkan, belum aktif
        //   active      → Sedang dipakai
        //   maintenance → Sedang diperbaiki
        //   disposed    → Sudah dihapusbukukan
        //   lost        → Hilang
        //   cancelled   → Pendaftaran dibatalkan
        //
        $assetsData = [
            // ─── ACTIVE ─────────────────────────────────────────────
            [
                'asset_code'       => 'FA-000001',
                'name'             => 'Laptop Admin',
                'asset_model_id'   => $laptopModel?->id,
                'asset_category_id'=> $itCategory?->id,
                'serial_number'    => 'SN-DELL-001',
                'branch_id'        => $headOffice->id,
                'asset_location_id'=> $itRoom?->id,
                'department_id'    => $defaultDepartment?->id,
                'employee_id'      => $adminEmployee?->id,
                'supplier_id'      => $supplier->id,
                'purchase_date'    => now()->subMonths(10)->toDateString(),
                'purchase_cost'    => 18000000,
                'currency'         => 'IDR',
                'warranty_end_date'=> now()->addMonths(14)->toDateString(),
                'status'           => 'active',
                'condition'        => 'good',
                'depreciation_method'     => 'straight_line',
                'depreciation_start_date' => now()->subMonths(10)->toDateString(),
                'useful_life_months'      => 36,
                'salvage_value'           => 0,
                'accumulated_depreciation'=> 0,
                'book_value'              => 18000000,
                'pipeline_state'   => 'active',
            ],
            [
                'asset_code'       => 'FA-000002',
                'name'             => 'Printer Office Lt. 2',
                'asset_model_id'   => $printerModel?->id,
                'asset_category_id'=> $itCategory?->id,
                'serial_number'    => 'SN-HP-001',
                'branch_id'        => $headOffice->id,
                'asset_location_id'=> $office?->id,
                'department_id'    => $defaultDepartment?->id,
                'employee_id'      => null,
                'supplier_id'      => $supplier->id,
                'purchase_date'    => now()->subMonths(6)->toDateString(),
                'purchase_cost'    => 4500000,
                'currency'         => 'IDR',
                'warranty_end_date'=> now()->addMonths(18)->toDateString(),
                'status'           => 'active',
                'condition'        => 'good',
                'depreciation_method'     => 'straight_line',
                'depreciation_start_date' => now()->subMonths(6)->toDateString(),
                'useful_life_months'      => 36,
                'salvage_value'           => 0,
                'accumulated_depreciation'=> 0,
                'book_value'              => 4500000,
                'pipeline_state'   => 'active',
            ],
            [
                'asset_code'       => 'FA-000003',
                'name'             => 'Mobil Operasional Toyota Avanza',
                'asset_model_id'   => $carModel?->id,
                'asset_category_id'=> $vehCategory?->id,
                'serial_number'    => 'SN-TYT-001',
                'branch_id'        => $headOffice->id,
                'asset_location_id'=> null,
                'department_id'    => $defaultDepartment?->id,
                'employee_id'      => $adminEmployee?->id,
                'supplier_id'      => $supplier->id,
                'purchase_date'    => now()->subMonths(20)->toDateString(),
                'purchase_cost'    => 260000000,
                'currency'         => 'IDR',
                'warranty_end_date'=> null,
                'status'           => 'active',
                'condition'        => 'good',
                'depreciation_method'     => 'straight_line',
                'depreciation_start_date' => now()->subMonths(20)->toDateString(),
                'useful_life_months'      => 60,
                'salvage_value'           => 0,
                'accumulated_depreciation'=> 0,
                'book_value'              => 260000000,
                'pipeline_state'   => 'active',
            ],

            // ─── DRAFT ──────────────────────────────────────────────
            [
                'asset_code'       => 'FA-000004',
                'name'             => 'Meja Kerja Baru (Belum Aktif)',
                'asset_model_id'   => $deskModel?->id,
                'asset_category_id'=> $offCategory?->id,
                'serial_number'    => 'SN-IKEA-001',
                'branch_id'        => $headOffice->id,
                'asset_location_id'=> $warehouse?->id,
                'department_id'    => $defaultDepartment?->id,
                'employee_id'      => null,
                'supplier_id'      => $supplier->id,
                'purchase_date'    => now()->subDays(5)->toDateString(),
                'purchase_cost'    => 3500000,
                'currency'         => 'IDR',
                'warranty_end_date'=> now()->addMonths(12)->toDateString(),
                'status'           => 'draft',
                'condition'        => 'good',
                'depreciation_method'     => 'straight_line',
                'depreciation_start_date' => null,
                'useful_life_months'      => 60,
                'salvage_value'           => 0,
                'accumulated_depreciation'=> 0,
                'book_value'              => 3500000,
                'pipeline_state'   => 'draft',
            ],
            [
                'asset_code'       => 'FA-000005',
                'name'             => 'Proyektor Ruang Meeting (Belum Aktif)',
                'asset_model_id'   => $projectorModel?->id,
                'asset_category_id'=> $itCategory?->id,
                'serial_number'    => 'SN-EPS-001',
                'branch_id'        => $headOffice->id,
                'asset_location_id'=> $warehouse?->id,
                'department_id'    => null,
                'employee_id'      => null,
                'supplier_id'      => $supplier->id,
                'purchase_date'    => now()->subDays(2)->toDateString(),
                'purchase_cost'    => 8500000,
                'currency'         => 'IDR',
                'warranty_end_date'=> now()->addMonths(24)->toDateString(),
                'status'           => 'draft',
                'condition'        => 'good',
                'depreciation_method'     => 'straight_line',
                'depreciation_start_date' => null,
                'useful_life_months'      => 48,
                'salvage_value'           => 0,
                'accumulated_depreciation'=> 0,
                'book_value'              => 8500000,
                'pipeline_state'   => 'draft',
            ],

            // ─── IN MAINTENANCE ─────────────────────────────────────
            [
                'asset_code'       => 'FA-000006',
                'name'             => 'Mesin Packing Gudang',
                'asset_model_id'   => $machineModel?->id,
                'asset_category_id'=> $mchCategory?->id,
                'serial_number'    => 'SN-MCH-001',
                'branch_id'        => $headOffice->id,
                'asset_location_id'=> $warehouse?->id,
                'department_id'    => $defaultDepartment?->id,
                'employee_id'      => null,
                'supplier_id'      => $supplier->id,
                'purchase_date'    => now()->subMonths(18)->toDateString(),
                'purchase_cost'    => 45000000,
                'currency'         => 'IDR',
                'warranty_end_date'=> now()->subMonths(6)->toDateString(),
                'status'           => 'maintenance',
                'condition'        => 'needs_repair',
                'depreciation_method'     => 'straight_line',
                'depreciation_start_date' => now()->subMonths(18)->toDateString(),
                'useful_life_months'      => 60,
                'salvage_value'           => 0,
                'accumulated_depreciation'=> 0,
                'book_value'              => 45000000,
                'pipeline_state'   => 'maintenance',
            ],

            // ─── DISPOSED ───────────────────────────────────────────
            [
                'asset_code'       => 'FA-000007',
                'name'             => 'Laptop Lama (Sudah Dihapusbukukan)',
                'asset_model_id'   => $thinkpadModel?->id,
                'asset_category_id'=> $itCategory?->id,
                'serial_number'    => 'SN-LNV-001',
                'branch_id'        => $headOffice->id,
                'asset_location_id'=> null,
                'department_id'    => $defaultDepartment?->id,
                'employee_id'      => null,
                'supplier_id'      => $supplier->id,
                'purchase_date'    => now()->subYears(4)->toDateString(),
                'purchase_cost'    => 22000000,
                'currency'         => 'IDR',
                'warranty_end_date'=> now()->subYears(2)->toDateString(),
                'status'           => 'disposed',
                'condition'        => 'damaged',
                'depreciation_method'     => 'straight_line',
                'depreciation_start_date' => now()->subYears(4)->toDateString(),
                'useful_life_months'      => 36,
                'salvage_value'           => 0,
                'accumulated_depreciation'=> 22000000,
                'book_value'              => 0,
                'pipeline_state'   => 'disposed',
            ],

            // ─── LOST ───────────────────────────────────────────────
            [
                'asset_code'       => 'FA-000008',
                'name'             => 'Filing Cabinet (Hilang Saat Pindah Kantor)',
                'asset_model_id'   => $cabinetModel?->id,
                'asset_category_id'=> $offCategory?->id,
                'serial_number'    => 'SN-KRS-001',
                'branch_id'        => $headOffice->id,
                'asset_location_id'=> null,
                'department_id'    => $defaultDepartment?->id,
                'employee_id'      => null,
                'supplier_id'      => $supplier->id,
                'purchase_date'    => now()->subYears(2)->toDateString(),
                'purchase_cost'    => 2800000,
                'currency'         => 'IDR',
                'warranty_end_date'=> null,
                'status'           => 'lost',
                'condition'        => 'good',
                'depreciation_method'     => 'straight_line',
                'depreciation_start_date' => now()->subYears(2)->toDateString(),
                'useful_life_months'      => 60,
                'salvage_value'           => 0,
                'accumulated_depreciation'=> 0,
                'book_value'              => 2800000,
                'pipeline_state'   => 'lost',
            ],

            // ─── CANCELLED ──────────────────────────────────────────
            [
                'asset_code'       => 'FA-000009',
                'name'             => 'Meja Kerja (Dibatalkan - Salah Order)',
                'asset_model_id'   => $deskModel?->id,
                'asset_category_id'=> $offCategory?->id,
                'serial_number'    => 'SN-IKEA-002',
                'branch_id'        => $headOffice->id,
                'asset_location_id'=> null,
                'department_id'    => null,
                'employee_id'      => null,
                'supplier_id'      => $supplier->id,
                'purchase_date'    => now()->subMonths(1)->toDateString(),
                'purchase_cost'    => 3500000,
                'currency'         => 'IDR',
                'warranty_end_date'=> null,
                'status'           => 'draft',  // DB enum doesn't have 'cancelled'; pipeline state tracks it
                'condition'        => 'good',
                'depreciation_method'     => 'straight_line',
                'depreciation_start_date' => null,
                'useful_life_months'      => 60,
                'salvage_value'           => 0,
                'accumulated_depreciation'=> 0,
                'book_value'              => 3500000,
                'pipeline_state'   => 'cancelled',
            ],
        ];

        // ── Create Assets + Initial Movements ───────────────────────
        foreach ($assetsData as $data) {
            $pipelineStateCode = $data['pipeline_state'];
            unset($data['pipeline_state']);

            $asset = Asset::updateOrCreate(['asset_code' => $data['asset_code']], $data);

            AssetMovement::updateOrCreate(
                ['asset_id' => $asset->id, 'movement_type' => 'acquired', 'moved_at' => $asset->purchase_date],
                [
                    'from_branch_id'     => null,
                    'to_branch_id'       => $asset->branch_id,
                    'from_location_id'   => null,
                    'to_location_id'     => $asset->asset_location_id,
                    'from_department_id' => null,
                    'to_department_id'   => $asset->department_id,
                    'from_employee_id'   => null,
                    'to_employee_id'     => $asset->employee_id,
                    'reference'          => 'INIT',
                    'notes'              => null,
                    'created_by'         => $adminUser?->id,
                ]
            );
        }

        // ── Pipeline Assignment ─────────────────────────────────────
        $pipeline = Pipeline::where('code', 'asset_lifecycle')->where('is_active', true)->first();
        if (! $pipeline) return;

        $pipelineStates = PipelineState::where('pipeline_id', $pipeline->id)->get()->keyBy('code');
        $transitions    = PipelineTransition::where('pipeline_id', $pipeline->id)->with(['fromState', 'toState'])->get();

        // Map transition by "from_code -> to_code"
        $transitionMap = [];
        foreach ($transitions as $t) {
            $key = $t->fromState->code . '->' . $t->toState->code;
            $transitionMap[$key] = $t;
        }

        // Define the transition paths each asset took to reach its current state
        // Each entry: [from_state_code, to_state_code, comment, days_ago]
        $transitionHistory = [
            // FA-000001: draft → active (10 months ago)
            'FA-000001' => [
                ['draft', 'active', null, 300],
            ],
            // FA-000002: draft → active (6 months ago)
            'FA-000002' => [
                ['draft', 'active', null, 180],
            ],
            // FA-000003: draft → active (20 months ago)
            'FA-000003' => [
                ['draft', 'active', null, 600],
            ],
            // FA-000004: stays in draft (no transitions)
            'FA-000004' => [],
            // FA-000005: stays in draft (no transitions)
            'FA-000005' => [],
            // FA-000006: draft → active → maintenance
            'FA-000006' => [
                ['draft', 'active', null, 540],
                ['active', 'maintenance', null, 14],
            ],
            // FA-000007: draft → active → disposed
            'FA-000007' => [
                ['draft', 'active', null, 1440],
                ['active', 'disposed', 'Laptop sudah melewati masa manfaat 3 tahun, layar retak dan keyboard tidak berfungsi. Disetujui untuk dihapusbukukan.', 60],
            ],
            // FA-000008: draft → active → lost
            'FA-000008' => [
                ['draft', 'active', null, 700],
                ['active', 'lost', 'Filing cabinet tidak ditemukan setelah proses pindah kantor dari Gedung A ke Gedung B. Sudah dicari selama 2 minggu.', 30],
            ],
            // FA-000009: draft → cancelled
            'FA-000009' => [
                ['draft', 'cancelled', null, 25],
            ],
        ];

        foreach ($assetsData as $data) {
            $asset = Asset::where('asset_code', $data['asset_code'])->first();
            if (! $asset) continue;

            $targetStateCode = $data['pipeline_state'] ?? 'draft';
            $targetState = $pipelineStates[$targetStateCode] ?? null;
            if (! $targetState) continue;

            // Skip if already assigned
            $existing = PipelineEntityState::where('pipeline_id', $pipeline->id)
                ->where('entity_type', 'App\Models\Asset')
                ->where('entity_id', $asset->id)
                ->first();
            if ($existing) continue;

            $initialState = $pipelineStates['draft'];

            // Create entity state record (final state)
            $entityState = PipelineEntityState::create([
                'pipeline_id'          => $pipeline->id,
                'entity_type'          => 'App\Models\Asset',
                'entity_id'            => $asset->id,
                'current_state_id'     => $targetState->id,
                'last_transitioned_by' => $adminUser?->id,
                'last_transitioned_at' => now(),
            ]);

            // Create initial log (assigned to draft)
            $history = $transitionHistory[$data['asset_code']] ?? [];
            $daysAgoInitial = ! empty($history) ? ($history[0][3] ?? 0) + 1 : 5;

            PipelineStateLog::create([
                'pipeline_entity_state_id' => $entityState->id,
                'entity_type'              => 'App\Models\Asset',
                'entity_id'                => $asset->id,
                'from_state_id'            => null,
                'to_state_id'              => $initialState->id,
                'transition_id'            => null,
                'performed_by'             => $adminUser?->id,
                'comment'                  => 'Initial pipeline assignment',
                'created_at'               => now()->subDays($daysAgoInitial),
            ]);

            // Create transition logs
            foreach ($history as $step) {
                [$fromCode, $toCode, $comment, $daysAgo] = $step;
                $transKey = "{$fromCode}->{$toCode}";
                $transition = $transitionMap[$transKey] ?? null;
                $fromState = $pipelineStates[$fromCode] ?? null;
                $toState   = $pipelineStates[$toCode] ?? null;

                if (! $fromState || ! $toState) continue;

                PipelineStateLog::create([
                    'pipeline_entity_state_id' => $entityState->id,
                    'entity_type'              => 'App\Models\Asset',
                    'entity_id'                => $asset->id,
                    'from_state_id'            => $fromState->id,
                    'to_state_id'              => $toState->id,
                    'transition_id'            => $transition?->id,
                    'performed_by'             => $adminUser?->id,
                    'comment'                  => $comment,
                    'created_at'               => now()->subDays($daysAgo),
                ]);
            }
        }

        // ── Additional Sample Data (Movements, Maintenance, etc.) ───
        $laptopAsset  = Asset::where('asset_code', 'FA-000001')->first();
        $printerAsset = Asset::where('asset_code', 'FA-000002')->first();
        $carAsset     = Asset::where('asset_code', 'FA-000003')->first();
        $machineAsset = Asset::where('asset_code', 'FA-000006')->first();

        // Transfer: Printer moved from IT Room to Office
        if ($printerAsset && $office && $itRoom && $defaultDepartment) {
            if (! AssetMovement::where('asset_id', $printerAsset->id)->where('movement_type', 'transfer')->exists()) {
                AssetMovement::create([
                    'asset_id'           => $printerAsset->id,
                    'movement_type'      => 'transfer',
                    'moved_at'           => now()->subMonths(2)->toDateString(),
                    'from_branch_id'     => $headOffice->id,
                    'to_branch_id'       => $headOffice->id,
                    'from_location_id'   => $itRoom->id,
                    'to_location_id'     => $office->id,
                    'from_department_id' => $defaultDepartment->id,
                    'to_department_id'   => $defaultDepartment->id,
                    'reference'          => 'TRF-001',
                    'notes'              => 'Dipindahkan ke lantai 2 untuk penggunaan bersama.',
                    'created_by'         => $adminUser?->id,
                ]);
            }
        }

        // Maintenance: Laptop screen replacement (completed)
        if ($laptopAsset && ! \App\Models\AssetMaintenance::where('asset_id', $laptopAsset->id)->exists()) {
            \App\Models\AssetMaintenance::create([
                'asset_id'         => $laptopAsset->id,
                'supplier_id'      => $supplier->id,
                'maintenance_type' => 'corrective',
                'notes'            => 'Penggantian layar LCD — layar retak akibat terjatuh.',
                'scheduled_at'     => now()->subMonths(3)->toDateString(),
                'performed_at'     => now()->subMonths(3)->addDays(2)->toDateString(),
                'cost'             => 1500000,
                'status'           => 'completed',
                'created_by'       => $adminUser?->id,
            ]);
        }

        // Maintenance: Machine repair (in progress)
        if ($machineAsset && ! \App\Models\AssetMaintenance::where('asset_id', $machineAsset->id)->exists()) {
            \App\Models\AssetMaintenance::create([
                'asset_id'         => $machineAsset->id,
                'supplier_id'      => $supplier->id,
                'maintenance_type' => 'corrective',
                'notes'            => 'Motor penggerak belt conveyor mati. Menunggu spare part dari supplier.',
                'scheduled_at'     => now()->subDays(14)->toDateString(),
                'cost'             => 7500000,
                'status'           => 'in_progress',
                'created_by'       => $adminUser?->id,
            ]);
        }

        // Maintenance: Car routine service (scheduled)
        if ($carAsset && ! \App\Models\AssetMaintenance::where('asset_id', $carAsset->id)->exists()) {
            \App\Models\AssetMaintenance::create([
                'asset_id'         => $carAsset->id,
                'supplier_id'      => $supplier->id,
                'maintenance_type' => 'preventive',
                'notes'            => 'Servis rutin 10.000 KM — ganti oli, filter, dan pengecekan umum.',
                'scheduled_at'     => now()->addDays(5)->toDateString(),
                'status'           => 'scheduled',
                'created_by'       => $adminUser?->id,
            ]);
        }

        // Stocktake
        if (! \App\Models\AssetStocktake::where('branch_id', $headOffice->id)->exists()) {
            $stocktake = \App\Models\AssetStocktake::create([
                'branch_id'    => $headOffice->id,
                'reference'    => 'STK-' . date('Ym'),
                'planned_at'   => now()->subDays(1),
                'performed_at' => now(),
                'status'       => 'completed',
                'created_by'   => $adminUser?->id,
            ]);

            if ($laptopAsset) {
                \App\Models\AssetStocktakeItem::create([
                    'asset_stocktake_id'   => $stocktake->id,
                    'asset_id'             => $laptopAsset->id,
                    'expected_branch_id'   => $headOffice->id,
                    'expected_location_id' => $itRoom?->id,
                    'found_branch_id'      => $headOffice->id,
                    'found_location_id'    => $itRoom?->id,
                    'result'               => 'found',
                    'checked_at'           => now(),
                    'checked_by'           => $adminUser?->id,
                ]);
            }

            if ($printerAsset) {
                \App\Models\AssetStocktakeItem::create([
                    'asset_stocktake_id'   => $stocktake->id,
                    'asset_id'             => $printerAsset->id,
                    'expected_branch_id'   => $headOffice->id,
                    'expected_location_id' => $office?->id,
                    'result'               => 'missing',
                    'checked_at'           => now(),
                    'checked_by'           => $adminUser?->id,
                ]);
            }

            if ($carAsset) {
                \App\Models\AssetStocktakeItem::create([
                    'asset_stocktake_id'   => $stocktake->id,
                    'asset_id'             => $carAsset->id,
                    'expected_branch_id'   => $headOffice->id,
                    'expected_location_id' => null,
                    'found_branch_id'      => $headOffice->id,
                    'found_location_id'    => null,
                    'result'               => 'found',
                    'checked_at'           => now(),
                    'checked_by'           => $adminUser?->id,
                ]);
            }
        }

        // Depreciation Run
        $fiscalYear = \App\Models\FiscalYear::where('status', 'open')->first();
        if ($fiscalYear) {
            $periodStart = now()->subMonth()->startOfMonth();
            $periodEnd   = now()->subMonth()->endOfMonth();

            $expenseAcc = \App\Models\Account::where('type', 'expense')->first();
            $accumAcc   = \App\Models\Account::where('type', 'asset')->first();

            if ($expenseAcc && $accumAcc) {
                Asset::whereNull('depreciation_expense_account_id')->update([
                    'depreciation_expense_account_id' => $expenseAcc->id,
                    'accumulated_depr_account_id'     => $accumAcc->id,
                ]);

                if (! \App\Models\AssetDepreciationRun::where('fiscal_year_id', $fiscalYear->id)->where('period_start', $periodStart->toDateString())->exists()) {
                    $calculateAction = app(\App\Actions\AssetDepreciationRuns\CalculateDepreciationAction::class);
                    $run = $calculateAction->execute([
                        'fiscal_year_id' => $fiscalYear->id,
                        'period_start'   => $periodStart->toDateString(),
                        'period_end'     => $periodEnd->toDateString(),
                    ]);

                    $postAction = app(\App\Actions\AssetDepreciationRuns\PostDepreciationToJournalAction::class);
                    $postAction->execute($run);
                }
            }
        }
    }
}
