import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';

type StockMonitorFilterOptions = {
    products: SelectOption[];
    warehouses: SelectOption[];
    branches: SelectOption[];
    categories: SelectOption[];
};

export function createStockMonitorFilterFields(
    options: StockMonitorFilterOptions,
): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.stock_monitor.search_placeholder',
                fallback: 'Search product, category, warehouse, branch...',
            },
        ),
        createSelectFilterField(
            'product_id',
            { key: 'column.product', fallback: 'Product' },
            options.products,
            { key: 'filter_field.all_products', fallback: 'All products' },
        ),
        createSelectFilterField(
            'warehouse_id',
            { key: 'column.warehouse', fallback: 'Warehouse' },
            options.warehouses,
            { key: 'filter_field.all_warehouses', fallback: 'All warehouses' },
        ),
        createSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            options.branches,
            { key: 'filter_field.all_branches', fallback: 'All branches' },
        ),
        createSelectFilterField(
            'category_id',
            { key: 'column.category', fallback: 'Category' },
            options.categories,
            { key: 'filter_field.all_categories', fallback: 'All categories' },
        ),
        createTextFilterField(
            'low_stock_threshold',
            {
                key: 'column.low_stock_threshold',
                fallback: 'Low Stock Threshold',
            },
            { key: 'filter.example_10', fallback: 'Example: 10' },
        ),
    ];
}
