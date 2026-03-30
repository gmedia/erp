import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createGoodsReceiptReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search GR number, PO number, supplier, warehouse, or product...',
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            'Supplier',
            '/api/suppliers',
            'All suppliers',
        ),
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'All warehouses',
        ),
        createAsyncSelectFilterField(
            'product_id',
            'Product',
            '/api/products',
            'All products',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'draft', label: 'Draft' },
                { value: 'confirmed', label: 'Confirmed' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
            'All statuses',
        ),
        ...createDateRangeFilterFields(),
    ];
}
