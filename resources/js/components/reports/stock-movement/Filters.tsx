import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockMovementReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search product, category, warehouse, branch...',
        ),
        createAsyncSelectFilterField(
            'product_id',
            'Product',
            '/api/products',
            'All products',
        ),
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'All warehouses',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'All branches',
        ),
        createAsyncSelectFilterField(
            'category_id',
            'Category',
            '/api/product-categories',
            'All categories',
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
