<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A. products
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_category_id_foreign');
            $table->dropIndex('products_is_manufactured_index');
            $table->dropIndex('products_category_id_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('category_id', 'product_category_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('product_category_id')
                ->references('id')
                ->on('product_categories')
                ->onDelete('cascade');

            $table->index('product_category_id');

            $table->dropColumn([
                'markup_percentage',
                'is_recurring',
                'trial_period_days',
                'allow_one_time_purchase',
                'is_manufactured',
                'is_purchasable',
                'is_sellable',
                'is_taxable',
            ]);
        });

        // B. product_stocks
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->decimal('quantity_on_hand', 15, 2)->default(0)
                ->comment('Current physical stock')
                ->change();
            $table->decimal('quantity_reserved', 15, 2)->default(0)
                ->comment('Reserved for production orders or sales')
                ->change();
            $table->dropColumn('minimum_quantity');
        });

        // C. bill_of_materials
        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->renameColumn('quantity_required', 'quantity');
        });

        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->decimal('waste_percentage', 5, 2)->default(0)
                ->after('quantity');
        });

        // D. production_orders (total_cost intentionally NOT renamed — it stays as header cache)
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropIndex('production_orders_production_date_index');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->renameColumn('quantity_to_produce', 'quantity');
            $table->renameColumn('production_date', 'planned_start_date');
            $table->renameColumn('completion_date', 'planned_end_date');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->date('actual_start_date')->nullable()->after('planned_end_date');
            $table->date('actual_end_date')->nullable()->after('actual_start_date');
            $table->foreignId('unit_id')->after('quantity')
                ->constrained('units')
                ->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->after('notes')
                ->constrained('users')
                ->onDelete('set null');

            $table->date('planned_start_date')->nullable()->change();
        });

        // E. production_order_items
        Schema::table('production_order_items', function (Blueprint $table) {
            $table->dropForeign('production_order_items_raw_material_id_foreign');
            $table->dropIndex('production_order_items_raw_material_id_index');
        });

        Schema::table('production_order_items', function (Blueprint $table) {
            $table->renameColumn('raw_material_id', 'product_id');
            $table->renameColumn('total_cost', 'cost');
        });

        Schema::table('production_order_items', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->index('product_id');

            $table->decimal('quantity_planned', 15, 2)->default(0)
                ->after('product_id');
            $table->foreignId('unit_id')->after('quantity_planned')
                ->constrained('units')
                ->onDelete('cascade');
            $table->text('notes')->nullable()->after('cost');
        });

        // F. product_dependencies
        Schema::table('product_dependencies', function (Blueprint $table) {
            $table->dropForeign('product_dependencies_product_id_foreign');
            $table->dropForeign('product_dependencies_required_product_id_foreign');
            $table->dropUnique('product_deps_unique');
            $table->dropIndex('product_dependencies_dependency_type_index');
            $table->dropIndex('product_dependencies_required_product_id_index');
        });

        Schema::table('product_dependencies', function (Blueprint $table) {
            $table->renameColumn('required_product_id', 'related_product_id');
            $table->renameColumn('dependency_type', 'type');
            $table->renameColumn('description', 'notes');
        });

        Schema::table('product_dependencies', function (Blueprint $table) {
            $table->dropColumn(['minimum_quantity', 'is_active']);

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->foreign('related_product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->index('related_product_id');
            $table->index('type');

            $table->unique(
                ['product_id', 'related_product_id', 'type'],
                'product_dependencies_product_id_related_product_id_type_unique'
            );
        });

        // G. product_prices
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropForeign('product_prices_product_id_foreign');
            $table->dropForeign('product_prices_customer_category_id_foreign');
            $table->dropUnique('product_prices_unique');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_category_id')->nullable()->change();
            $table->date('effective_from')->nullable(false)->change();
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->foreign('customer_category_id')
                ->references('id')
                ->on('customer_categories')
                ->onDelete('set null');

            $table->unique(
                ['product_id', 'customer_category_id', 'effective_from'],
                'product_prices_product_category_effective_unique'
            );
        });
    }

    public function down(): void
    {
        // G. product_prices — rollback
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['customer_category_id']);
            $table->dropUnique('product_prices_product_category_effective_unique');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->date('effective_from')->nullable()->change();
            $table->unsignedBigInteger('customer_category_id')->nullable(false)->change();
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->foreign('customer_category_id')
                ->references('id')
                ->on('customer_categories')
                ->onDelete('cascade');

            $table->unique(
                ['product_id', 'customer_category_id', 'effective_from'],
                'product_prices_unique'
            );
        });

        // F. product_dependencies — rollback
        Schema::table('product_dependencies', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['related_product_id']);
            $table->dropUnique('product_dependencies_product_id_related_product_id_type_unique');
            $table->dropIndex('product_dependencies_related_product_id_index');
            $table->dropIndex('product_dependencies_type_index');
        });

        Schema::table('product_dependencies', function (Blueprint $table) {
            $table->integer('minimum_quantity')->default(1)
                ->after('type')
                ->comment('Minimum quantity of required product needed');
            $table->boolean('is_active')->default(true)->after('notes');
        });

        Schema::table('product_dependencies', function (Blueprint $table) {
            $table->renameColumn('related_product_id', 'required_product_id');
            $table->renameColumn('type', 'dependency_type');
            $table->renameColumn('notes', 'description');
        });

        Schema::table('product_dependencies', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->foreign('required_product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->index('required_product_id');
            $table->index('dependency_type');

            $table->unique(
                ['product_id', 'required_product_id', 'dependency_type'],
                'product_deps_unique'
            );
        });

        // E. production_order_items — rollback
        Schema::table('production_order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropIndex('production_order_items_product_id_index');
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['quantity_planned', 'unit_id', 'notes']);
        });

        Schema::table('production_order_items', function (Blueprint $table) {
            $table->renameColumn('product_id', 'raw_material_id');
            $table->renameColumn('cost', 'total_cost');
        });

        Schema::table('production_order_items', function (Blueprint $table) {
            $table->foreign('raw_material_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
            $table->index('raw_material_id');
        });

        // D. production_orders — rollback
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['actual_start_date', 'actual_end_date', 'unit_id', 'created_by']);
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->date('planned_start_date')->nullable(false)->change();
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->renameColumn('quantity', 'quantity_to_produce');
            $table->renameColumn('planned_start_date', 'production_date');
            $table->renameColumn('planned_end_date', 'completion_date');
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->index('production_date');
        });

        // C. bill_of_materials — rollback
        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->dropColumn('waste_percentage');
        });

        Schema::table('bill_of_materials', function (Blueprint $table) {
            $table->renameColumn('quantity', 'quantity_required');
        });

        // B. product_stocks — rollback
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->integer('quantity_on_hand')->default(0)
                ->comment('Current physical stock')
                ->change();
            $table->integer('quantity_reserved')->default(0)
                ->comment('Reserved for production orders or sales')
                ->change();
            $table->integer('minimum_quantity')->default(0)
                ->after('quantity_reserved')
                ->comment('Reorder point');
        });

        // A. products — rollback
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_category_id']);
            $table->dropIndex('products_product_category_id_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('product_category_id', 'category_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('product_categories')
                ->onDelete('cascade');
            $table->index('category_id');

            $table->decimal('markup_percentage', 5, 2)->nullable()
                ->after('selling_price')
                ->comment('Markup % over cost');
            $table->boolean('is_recurring')->default(false)
                ->after('billing_model')
                ->comment('TRUE if this is a subscription product');
            $table->integer('trial_period_days')->nullable()
                ->after('is_recurring')
                ->comment('Free trial period in days (null = no trial)');
            $table->boolean('allow_one_time_purchase')->default(true)
                ->after('trial_period_days')
                ->comment('Allow buying without subscription');
            $table->boolean('is_manufactured')->default(false)
                ->after('allow_one_time_purchase')
                ->comment('TRUE if this product is manufactured (has BOM)');
            $table->boolean('is_purchasable')->default(true)
                ->after('is_manufactured')
                ->comment('TRUE if can be purchased from suppliers');
            $table->boolean('is_sellable')->default(true)
                ->after('is_purchasable')
                ->comment('TRUE if can be sold to customers');
            $table->boolean('is_taxable')->default(true)
                ->after('is_sellable');

            $table->index('is_manufactured');
        });
    }
};
