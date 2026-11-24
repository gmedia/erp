<?php

namespace Database\Seeders;

use App\Models\BillOfMaterial;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerSubscription;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDependency;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderItem;
use App\Models\SubscriptionBillingRecord;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $electronicsCategory = ProductCategory::where('name', DemoMasterData::PRODUCT_CATEGORY_ELECTRONICS)->first();
        $furnitureCategory = ProductCategory::where('name', DemoMasterData::PRODUCT_CATEGORY_FINISHED_FURNITURE)->first();
        $rawMaterialWood = ProductCategory::where('name', DemoMasterData::PRODUCT_CATEGORY_RAW_WOOD)->first();
        $rawMaterialHardware = ProductCategory::where('name', DemoMasterData::PRODUCT_CATEGORY_RAW_HARDWARE)->first();
        $softwareCategory = ProductCategory::where('name', DemoMasterData::PRODUCT_CATEGORY_SOFTWARE)->first();

        $pieceUnit = Unit::where('symbol', 'pcs')->first();
        $meterUnit = Unit::where('symbol', 'm')->first();
        $sheetUnit = Unit::where('name', DemoMasterData::UNIT_SHEET)->first();
        $setUnit = Unit::where('name', DemoMasterData::UNIT_SET)->first();
        $boxUnit = Unit::where('name', DemoMasterData::UNIT_BOX)->first();
        $serviceUnit = Unit::where('name', DemoMasterData::UNIT_SERVICE)->first();

        $woodPanel = Product::updateOrCreate(
            ['code' => 'RM-WOOD-001'],
            [
                'name' => 'MDF Wood Panel 120x240cm',
                'description' => 'Medium Density Fiberboard for furniture manufacturing',
                'type' => 'raw_material',
                'product_category_id' => $rawMaterialWood->id,
                'unit_id' => $sheetUnit->id,
                'cost' => 250000,
                'selling_price' => 0,
                'status' => 'active',
            ]);

        $tableLegs = Product::updateOrCreate(
            ['code' => 'RM-WOOD-002'],
            [
                'name' => 'Wooden Table Legs (Set of 4)',
                'description' => 'Premium wooden table legs',
                'type' => 'raw_material',
                'product_category_id' => $rawMaterialWood->id,
                'unit_id' => $setUnit->id,
                'cost' => 180000,
                'selling_price' => 0,
                'status' => 'active',
            ]);

        $screws = Product::updateOrCreate(
            ['code' => 'RM-HW-001'],
            [
                'name' => 'Wood Screws 4x40mm (Box of 100)',
                'description' => 'Quality wood screws for furniture assembly',
                'type' => 'raw_material',
                'product_category_id' => $rawMaterialHardware->id,
                'unit_id' => $boxUnit->id,
                'cost' => 25000,
                'selling_price' => 0,
                'status' => 'active',
            ]);

        $officeDesk = Product::updateOrCreate(
            ['code' => 'FG-FURN-001'],
            [
                'name' => 'Executive Office Desk',
                'description' => 'Premium executive office desk with storage',
                'type' => 'finished_good',
                'product_category_id' => $furnitureCategory->id,
                'unit_id' => $pieceUnit->id,
                'cost' => 850000,
                'selling_price' => 1500000,
                'status' => 'active',
            ]);

        BillOfMaterial::updateOrCreate(
            [
                'finished_product_id' => $officeDesk->id,
                'raw_material_id' => $woodPanel->id,
            ],
            [
                'quantity' => 2,
                'waste_percentage' => 5,
                'unit_id' => $sheetUnit->id,
                'notes' => 'Top panel and bottom shelf',
            ]);

        BillOfMaterial::updateOrCreate(
            [
                'finished_product_id' => $officeDesk->id,
                'raw_material_id' => $tableLegs->id,
            ],
            [
                'quantity' => 1,
                'waste_percentage' => 0,
                'unit_id' => $setUnit->id,
                'notes' => 'Table legs support',
            ]);

        BillOfMaterial::updateOrCreate(
            [
                'finished_product_id' => $officeDesk->id,
                'raw_material_id' => $screws->id,
            ],
            [
                'quantity' => 2,
                'waste_percentage' => 10,
                'unit_id' => $boxUnit->id,
                'notes' => 'Assembly screws',
            ]);

        $branchId = Branch::query()->value('id') ?? Branch::create(['name' => DemoMasterData::BRANCH_HEAD_OFFICE])->id;

        $productionOrder = ProductionOrder::updateOrCreate(
            ['order_number' => 'MO-2026-000001'],
            [
                'product_id' => $officeDesk->id,
                'branch_id' => $branchId,
                'quantity' => 10,
                'unit_id' => $pieceUnit->id,
                'planned_start_date' => now()->subDays(7),
                'planned_end_date' => now()->subDays(1),
                'actual_start_date' => now()->subDays(6),
                'actual_end_date' => now()->subDays(1),
                'status' => 'completed',
                'total_cost' => 8500000,
            ]);

        // Idempoten: bersihkan item demo lama untuk order ini.
        ProductionOrderItem::query()
            ->where('production_order_id', $productionOrder->id)
            ->delete();

        ProductionOrderItem::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $woodPanel->id,
            'quantity_planned' => 20,
            'unit_id' => $sheetUnit->id,
            'quantity_used' => 20,
            'unit_cost' => 250000,
            'cost' => 5000000,
        ]);

        ProductionOrderItem::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $tableLegs->id,
            'quantity_planned' => 10,
            'unit_id' => $setUnit->id,
            'quantity_used' => 10,
            'unit_cost' => 180000,
            'cost' => 1800000,
        ]);

        ProductionOrderItem::create([
            'production_order_id' => $productionOrder->id,
            'product_id' => $screws->id,
            'quantity_planned' => 20,
            'unit_id' => $boxUnit->id,
            'quantity_used' => 20,
            'unit_cost' => 25000,
            'cost' => 500000,
        ]);

        // Kode placeholder SaaS-CLOUD-001 diganti (W7-17). Baris lama di-rename
        // in-place agar langganan & plan yang menempel tetap terhubung.
        Product::query()
            ->where('code', 'SaaS-CLOUD-001')
            ->when(
                ! Product::query()->where('code', 'SW-CLD-001')->exists(),
                fn ($query) => $query->update(['code' => 'SW-CLD-001']),
            );

        $cloudStorage = Product::updateOrCreate(
            ['code' => 'SW-CLD-001'],
            [
                'name' => 'Layanan Penyimpanan Awan Pro',
                'description' => 'Layanan penyimpanan awan dengan fitur lanjutan untuk perusahaan',
                'type' => 'service',
                'product_category_id' => $softwareCategory->id,
                'unit_id' => $serviceUnit->id,
                'cost' => 30000,
                'selling_price' => 99000,
                'billing_model' => 'subscription',
                'status' => 'active',
            ]);

        $monthlyPlan = SubscriptionPlan::updateOrCreate(
            [
                'product_id' => $cloudStorage->id,
                'name' => 'Layanan Penyimpanan Awan Pro - Bulanan',
            ],
            [
                'billing_interval' => 'monthly',
                'price' => 99000,
                'setup_fee' => 0,
                'trial_period_days' => 14,
                'is_active' => true,
            ]);

        SubscriptionPlan::updateOrCreate(
            [
                'product_id' => $cloudStorage->id,
                'name' => 'Layanan Penyimpanan Awan Pro - Tahunan',
            ],
            [
                'billing_interval' => 'annual',
                'price' => 990000,
                'setup_fee' => 0,
                'trial_period_days' => 14,
                'is_active' => true,
            ]);

        if (Customer::count() > 0) {
            $customer = Customer::first();

            $subscription = CustomerSubscription::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'subscription_plan_id' => $monthlyPlan->id,
                ],
                [
                    'start_date' => now()->subMonths(3),
                    'end_date' => null,
                    'next_billing_date' => now()->addDays(15),
                    'status' => 'active',
                ]);

            SubscriptionBillingRecord::query()
                ->where('customer_subscription_id', $subscription->id)
                ->delete();

            SubscriptionBillingRecord::create([
                'customer_subscription_id' => $subscription->id,
                'billing_period_start' => now()->subMonth()->startOfMonth(),
                'billing_period_end' => now()->subMonth()->endOfMonth(),
                'amount' => 99000,
                'tax_amount' => 10890,
                'discount_amount' => 0,
                'total' => 109890,
                'status' => 'paid',
                'paid_at' => now()->subMonth()->startOfMonth()->addDays(3),
            ]);
        }

        $deskChair = Product::updateOrCreate(
            ['code' => 'FG-FURN-002'],
            [
                'name' => 'Ergonomic Office Chair',
                'description' => 'Comfortable ergonomic chair for office use',
                'type' => 'finished_good',
                'product_category_id' => $furnitureCategory->id,
                'unit_id' => $pieceUnit->id,
                'cost' => 450000,
                'selling_price' => 750000,
                'status' => 'active',
            ]);

        ProductDependency::updateOrCreate(
            [
                'product_id' => $officeDesk->id,
                'related_product_id' => $deskChair->id,
                'type' => 'recommended',
            ],
            [
                'notes' => 'Complete your office setup with a matching chair',
            ]);

        $this->command->info('Sample product data created successfully!');
    }
}
