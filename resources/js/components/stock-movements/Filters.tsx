import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockMovementsFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search reference, notes, product, warehouse...',
        ),
        createAsyncSelectFilterField(
            'product_id',
            'Product',
            '/api/products',
            'Select a product',
        ),
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'Select a warehouse',
        ),
        createSelectFilterField(
            'movement_type',
            'Movement Type',
            [
                { value: 'goods_receipt', label: 'Goods Receipt' },
                { value: 'supplier_return', label: 'Supplier Return' },
                { value: 'transfer_out', label: 'Transfer Out' },
                { value: 'transfer_in', label: 'Transfer In' },
                { value: 'adjustment_in', label: 'Adjustment In' },
                { value: 'adjustment_out', label: 'Adjustment Out' },
                { value: 'production_consume', label: 'Production Consume' },
                { value: 'production_output', label: 'Production Output' },
                { value: 'sales', label: 'Sales' },
                { value: 'sales_return', label: 'Sales Return' },
            ],
            'Select movement type',
        ),
        {
            name: 'start_date',
            label: 'Start Date',
            component: <FilterDatePicker placeholder="Start Date" />,
        },
        {
            name: 'end_date',
            label: 'End Date',
            component: <FilterDatePicker placeholder="End Date" />,
        },
    ];
}
