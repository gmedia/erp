import { FilterField } from '@/components/common/DataTableCore';
import { AsyncSelectFieldConfig } from '@/components/common/AsyncSelectField';

export function createBookValueReportFilterFields(): FilterField[] {
    return [
        {
            name: 'asset_category_id',
            label: 'Category',
            type: 'async-select',
            config: {
                url: '/api/asset-categories',
                labelFn: (item: any) => item.name,
                valueFn: (item: any) => String(item.id),
                placeholder: 'Filter by category...',
            } as AsyncSelectFieldConfig,
        },
        {
            name: 'branch_id',
            label: 'Branch',
            type: 'async-select',
            config: {
                url: '/api/branches',
                labelFn: (item: any) => item.name,
                valueFn: (item: any) => String(item.id),
                placeholder: 'Filter by branch...',
            } as AsyncSelectFieldConfig,
        },
    ];
}
