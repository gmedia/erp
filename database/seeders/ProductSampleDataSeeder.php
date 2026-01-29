<?php

namespace Database\Seeders;

use App\Models\BillOfMaterial;
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
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing categories and units from master data
        $electronicsCategory = ProductCategory::where('name', 'Electronics')->first();
        $furnitureCategory = ProductCategory::where('name', 'Finished Goods - Furniture')->first();
        $rawMaterialWood = ProductCategory::where('name', 'Raw Materials - Wood')->first();
        $rawMaterialHardware = ProductCategory::where('name', 'Raw Materials - Hardware')->first();
        $softwareCategory = ProductCategory::where('name', 'Software & SaaS')->first();

        $pieceUnit = Unit::where('symbol', 'pcs')->first();
        $meterUnit = Unit::where('symbol', 'm')->first();
        $sheetUnit = Unit::where('name', 'Sheet')->first();

        // === RAW MATERIALS ===
        $woodPanel = Product::create([
            'code' => 'RM-WOOD-001',
            'name' => 'MDF Wood Panel 120x240cm',
            'description' => 'Medium Density Fiberboard for furniture manufacturing',
            'type' => 'raw_material',
            'category_id' => $rawMaterialWood->id,
            'unit_id' => $sheetUnit->id,
            'cost' => 250000,
            'selling_price' => 0,
            'is_manufactured' => false,
            'is_purchasable' => true,
            'is_sellable' => false,
            'status' => 'active',
        ]);

        $tableLegs = Product::create([
            'code' => 'RM-WOOD-002',
            'name' => 'Wooden Table Legs (Set of 4)',
            'description' => 'Premium wooden table legs',
            'type' => 'raw_material',
            'category_id' => $rawMaterialWood->id,
            'unit_id' => Unit::where('name', 'Set')->first()->id,
            'cost' => 180000,
            'selling_price' => 0,
            'is_manufactured' => false,
            'is_purchasable' => true,
            'is_sellable' => false,
            'status' => 'active',
        ]);

        $screws = Product::create([
            'code' => 'RM-HW-001',
            'name' => 'Wood Screws 4x40mm (Box of 100)',
            'description' => 'Quality wood screws for furniture assembly',
            'type' => 'raw_material',
            'category_id' => $rawMaterialHardware->id,
            'unit_id' => Unit::where('name', 'Box')->first()->id,
            'cost' => 25000,
            'selling_price' => 0,
            'is_manufactured' => false,
            'is_purchasable' => true,
            'is_sellable' => false,
            'status' => 'active',
        ]);

        // === FINISHED GOODS (MANUFACTURED) ===
        $officeDesk = Product::create([
            'code' => 'FG-FURN-001',
            'name' => 'Executive Office Desk',
            'description' => 'Premium executive office desk with storage',
            'type' => 'finished_good',
            'category_id' => $furnitureCategory->id,
            'unit_id' => $pieceUnit->id,
            'cost' => 850000,
            'selling_price' => 1500000,
            'markup_percentage' => 76.47,
            'is_manufactured' => true,
            'is_purchasable' => false,
            'is_sellable' => true,
            'is_taxable' => true,
            'status' => 'active',
        ]);

        // Bill of Materials for Office Desk
        BillOfMaterial::create([
            'finished_product_id' => $officeDesk->id,
            'raw_material_id' => $woodPanel->id,
            'quantity_required' => 2,
            'unit_id' => $sheetUnit->id,
            'notes' => 'Top panel and bottom shelf',
        ]);

        BillOfMaterial::create([
            'finished_product_id' => $officeDesk->id,
            'raw_material_id' => $tableLegs->id,
            'quantity_required' => 1,
            'unit_id' => Unit::where('name', 'Set')->first()->id,
            'notes' => 'Table legs support',
        ]);

        BillOfMaterial::create([
            'finished_product_id' => $officeDesk->id,
            'raw_material_id' => $screws->id,
            'quantity_required' => 2,
            'unit_id' => Unit::where('name', 'Box')->first()->id,
            'notes' => 'Assembly screws',
        ]);

        // Production Orders
        $productionOrder = ProductionOrder::create([
            'order_number' => 'PO-20260129-0001',
            'product_id' => $officeDesk->id,
            'branch_id' => 1,
            'quantity_to_produce' => 10,
            'production_date' => now()->subDays(5),
            'completion_date' => now()->subDays(1),
            'status' => 'completed',
            'total_cost' => 8500000,
        ]);

        ProductionOrderItem::create([
            'production_order_id' => $productionOrder->id,
            'raw_material_id' => $woodPanel->id,
            'quantity_used' => 20,
            'unit_cost' => 250000,
            'total_cost' => 5000000,
        ]);

        ProductionOrderItem::create([
            'production_order_id' => $productionOrder->id,
            'raw_material_id' => $tableLegs->id,
            'quantity_used' => 10,
            'unit_cost' => 180000,
            'total_cost' => 1800000,
        ]);

        ProductionOrderItem::create([
            'production_order_id' => $productionOrder->id,
            'raw_material_id' => $screws->id,
            'quantity_used' => 20,
            'unit_cost' => 25000,
            'total_cost' => 500000,
        ]);

        // === SUBSCRIPTION PRODUCT (SOFTWARE/SAAS) ===
        $cloudStorage = Product::create([
            'code' => 'SaaS-CLOUD-001',
            'name' => 'Cloud Storage Pro',
            'description' => 'Premium cloud storage solution with advanced features',
            'type' => 'service',
            'category_id' => $softwareCategory->id,
            'unit_id' => Unit::where('name', 'Service')->first()->id,
            'cost' => 30000,
            'selling_price' => 99000,
            'markup_percentage' => 230,
            'billing_model' => 'subscription',
            'is_recurring' => true,
            'trial_period_days' => 14,
            'is_manufactured' => false,
            'is_purchasable' => false,
            'is_sellable' => true,
            'status' => 'active',
        ]);

        // Subscription Plans
        $monthlyPlan = SubscriptionPlan::create([
            'product_id' => $cloudStorage->id,
            'name' => 'Cloud Storage Pro - Monthly',
            'code' => 'CLOUD-MONTHLY',
            'description' => 'Monthly subscription with flexible cancellation',
            'billing_interval' => 'monthly',
            'billing_interval_count' => 1,
            'price' => 99000,
            'setup_fee' => 0,
            'trial_period_days' => 14,
            'auto_renew' => true,
            'status' => 'active',
        ]);

        $annualPlan = SubscriptionPlan::create([
            'product_id' => $cloudStorage->id,
            'name' => 'Cloud Storage Pro - Annual',
            'code' => 'CLOUD-ANNUAL',
            'description' => 'Annual subscription with 2 months free',
            'billing_interval' => 'annual',
            'billing_interval_count' => 1,
            'price' => 990000,
            'setup_fee' => 0,
            'trial_period_days' => 14,
            'minimum_commitment_cycles' => 1,
            'auto_renew' => true,
            'status' => 'active',
        ]);

        // Customer Subscriptions (if customers exist)
        if (Customer::count() > 0) {
            $customer = Customer::first();
            
            $subscription = CustomerSubscription::create([
                'subscription_number' => 'SUB-20260129-0001',
                'customer_id' => $customer->id,
                'subscription_plan_id' => $monthlyPlan->id,
                'product_id' => $cloudStorage->id,
                'status' => 'active',
                'start_date' => now()->subMonths(3),
                'current_period_start' => now()->startOfMonth(),
                'current_period_end' => now()->endOfMonth(),
                'billing_cycles_completed' => 3,
                'auto_renew' => true,
                'recurring_amount' => 99000,
            ]);

            // Billing Records
            SubscriptionBillingRecord::create([
                'customer_subscription_id' => $subscription->id,
                'invoice_number' => 'INV-SUB-20260129-0001',
                'period_start' => now()->startOfMonth(),
                'period_end' => now()->endOfMonth(),
                'billing_date' => now()->startOfMonth(),
                'due_date' => now()->startOfMonth()->addDays(7),
                'subtotal' => 99000,
                'tax_amount' => 10890,
                'total_amount' => 109890,
                'amount_paid' => 109890,
                'status' => 'paid',
                'paid_date' => now()->startOfMonth()->addDays(3),
                'payment_method' => 'credit_card',
                'payment_reference' => 'PAY-2026-0001',
            ]);
        }

        // === PRODUCT DEPENDENCIES ===
        $deskChair = Product::create([
            'code' => 'FG-FURN-002',
            'name' => 'Ergonomic Office Chair',
            'description' => 'Comfortable ergonomic chair for office use',
            'type' => 'finished_good',
            'category_id' => $furnitureCategory->id,
            'unit_id' => $pieceUnit->id,
            'cost' => 450000,
            'selling_price' => 750000,
            'is_purchasable' => true,
            'is_sellable' => true,
            'status' => 'active',
        ]);

        // Recommended: When buying desk, suggest chair
        ProductDependency::create([
            'product_id' => $officeDesk->id,
            'required_product_id' => $deskChair->id,
            'dependency_type' => 'recommended',
            'minimum_quantity' => 1,
            'description' => 'Complete your office setup with a matching chair',
            'is_active' => true,
        ]);

        // === STOCK RECORDS ===
        // Stock for raw materials
        ProductStock::create([
            'product_id' => $woodPanel->id,
            'branch_id' => 1,
            'quantity_on_hand' => 50,
            'quantity_reserved' => 10,
            'minimum_quantity' => 20,
            'average_cost' => 250000,
        ]);

        ProductStock::create([
            'product_id' => $tableLegs->id,
            'branch_id' => 1,
            'quantity_on_hand' => 30,
            'quantity_reserved' => 5,
            'minimum_quantity' => 15,
            'average_cost' => 180000,
        ]);

        // Stock for finished goods
        ProductStock::create([
            'product_id' => $officeDesk->id,
            'branch_id' => 1,
            'quantity_on_hand' => 8,
            'quantity_reserved' => 2,
            'minimum_quantity' => 5,
            'average_cost' => 850000,
        ]);

        $this->command->info('Sample product data created successfully!');
        $this->command->info('- Raw materials: 3 items');
        $this->command->info('- Finished goods: 2 items (1 with BOM)');
        $this->command->info('- SaaS product: 1 item with 2 subscription plans');
        $this->command->info('- Production order: 1 completed');
        $this->command->info('- Product dependencies: 1 recommendation');
        $this->command->info('- Stock records: 4 items');
    }
}
