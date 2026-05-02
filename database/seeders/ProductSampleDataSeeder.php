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
use App\Models\ProductStock;
use App\Models\SubscriptionBillingRecord;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class ProductSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $electronicsCategory = ProductCategory::where('name', 'Electronics')->first();
        $furnitureCategory = ProductCategory::where('name', 'Finished Goods - Furniture')->first();
        $rawMaterialWood = ProductCategory::where('name', 'Raw Materials - Wood')->first();
        $rawMaterialHardware = ProductCategory::where('name', 'Raw Materials - Hardware')->first();
        $softwareCategory = ProductCategory::where('name', 'Software & SaaS')->first();

        $pieceUnit = Unit::where('symbol', 'pcs')->first();
        $meterUnit = Unit::where('symbol', 'm')->first();
        $sheetUnit = Unit::where('name', 'Sheet')->first();
        $setUnit = Unit::where('name', 'Set')->first();
        $boxUnit = Unit::where('name', 'Box')->first();
        $serviceUnit = Unit::where('name', 'Service')->first();

        $woodPanel = Product::create([
            'code' => 'RM-WOOD-001',
            'name' => 'MDF Wood Panel 120x240cm',
            'description' => 'Medium Density Fiberboard for furniture manufacturing',
            'type' => 'raw_material',
            'product_category_id' => $rawMaterialWood->id,
            'unit_id' => $sheetUnit->id,
            'cost' => 250000,
            'selling_price' => 0,
            'status' => 'active',
        ]);

        $tableLegs = Product::create([
            'code' => 'RM-WOOD-002',
            'name' => 'Wooden Table Legs (Set of 4)',
            'description' => 'Premium wooden table legs',
            'type' => 'raw_material',
            'product_category_id' => $rawMaterialWood->id,
            'unit_id' => $setUnit->id,
            'cost' => 180000,
            'selling_price' => 0,
            'status' => 'active',
        ]);

        $screws = Product::create([
            'code' => 'RM-HW-001',
            'name' => 'Wood Screws 4x40mm (Box of 100)',
            'description' => 'Quality wood screws for furniture assembly',
            'type' => 'raw_material',
            'product_category_id' => $rawMaterialHardware->id,
            'unit_id' => $boxUnit->id,
            'cost' => 25000,
            'selling_price' => 0,
            'status' => 'active',
        ]);

        $officeDesk = Product::create([
            'code' => 'FG-FURN-001',
            'name' => 'Executive Office Desk',
            'description' => 'Premium executive office desk with storage',
            'type' => 'finished_good',
            'product_category_id' => $furnitureCategory->id,
            'unit_id' => $pieceUnit->id,
            'cost' => 850000,
            'selling_price' => 1500000,
            'status' => 'active',
        ]);

        BillOfMaterial::create([
            'finished_product_id' => $officeDesk->id,
            'raw_material_id' => $woodPanel->id,
            'quantity' => 2,
            'waste_percentage' => 5,
            'unit_id' => $sheetUnit->id,
            'notes' => 'Top panel and bottom shelf',
        ]);

        BillOfMaterial::create([
            'finished_product_id' => $officeDesk->id,
            'raw_material_id' => $tableLegs->id,
            'quantity' => 1,
            'waste_percentage' => 0,
            'unit_id' => $setUnit->id,
            'notes' => 'Table legs support',
        ]);

        BillOfMaterial::create([
            'finished_product_id' => $officeDesk->id,
            'raw_material_id' => $screws->id,
            'quantity' => 2,
            'waste_percentage' => 10,
            'unit_id' => $boxUnit->id,
            'notes' => 'Assembly screws',
        ]);

        $branchId = Branch::query()->value('id') ?? Branch::create(['name' => 'Head Office'])->id;

        $productionOrder = ProductionOrder::create([
            'order_number' => 'MO-2026-000001',
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

        $cloudStorage = Product::create([
            'code' => 'SaaS-CLOUD-001',
            'name' => 'Cloud Storage Pro',
            'description' => 'Premium cloud storage solution with advanced features',
            'type' => 'service',
            'product_category_id' => $softwareCategory->id,
            'unit_id' => $serviceUnit->id,
            'cost' => 30000,
            'selling_price' => 99000,
            'billing_model' => 'subscription',
            'status' => 'active',
        ]);

        $monthlyPlan = SubscriptionPlan::create([
            'product_id' => $cloudStorage->id,
            'name' => 'Cloud Storage Pro - Monthly',
            'billing_interval' => 'monthly',
            'price' => 99000,
            'setup_fee' => 0,
            'trial_period_days' => 14,
            'is_active' => true,
        ]);

        SubscriptionPlan::create([
            'product_id' => $cloudStorage->id,
            'name' => 'Cloud Storage Pro - Annual',
            'billing_interval' => 'annual',
            'price' => 990000,
            'setup_fee' => 0,
            'trial_period_days' => 14,
            'is_active' => true,
        ]);

        if (Customer::count() > 0) {
            $customer = Customer::first();

            $subscription = CustomerSubscription::create([
                'customer_id' => $customer->id,
                'subscription_plan_id' => $monthlyPlan->id,
                'start_date' => now()->subMonths(3),
                'end_date' => null,
                'next_billing_date' => now()->addDays(15),
                'status' => 'active',
            ]);

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

        $deskChair = Product::create([
            'code' => 'FG-FURN-002',
            'name' => 'Ergonomic Office Chair',
            'description' => 'Comfortable ergonomic chair for office use',
            'type' => 'finished_good',
            'product_category_id' => $furnitureCategory->id,
            'unit_id' => $pieceUnit->id,
            'cost' => 450000,
            'selling_price' => 750000,
            'status' => 'active',
        ]);

        ProductDependency::create([
            'product_id' => $officeDesk->id,
            'related_product_id' => $deskChair->id,
            'type' => 'recommended',
            'notes' => 'Complete your office setup with a matching chair',
        ]);

        ProductStock::create([
            'product_id' => $woodPanel->id,
            'branch_id' => $branchId,
            'quantity_on_hand' => 50,
            'quantity_reserved' => 10,
            'average_cost' => 250000,
        ]);

        ProductStock::create([
            'product_id' => $tableLegs->id,
            'branch_id' => $branchId,
            'quantity_on_hand' => 30,
            'quantity_reserved' => 5,
            'average_cost' => 180000,
        ]);

        ProductStock::create([
            'product_id' => $officeDesk->id,
            'branch_id' => $branchId,
            'quantity_on_hand' => 8,
            'quantity_reserved' => 2,
            'average_cost' => 850000,
        ]);

        $this->command->info('Sample product data created successfully!');
    }
}
