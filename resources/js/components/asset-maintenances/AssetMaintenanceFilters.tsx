import {
    type FieldDescriptor,
    createAssetSupplierFilterFields,
    createCostRangeFilterFields,
    createDateFilterFields,
    createMaintenanceTypeStatusFilterFields,
    createTextFilterField,
} from '@/components/common/filters';

export const createAssetMaintenanceFilterFields = (): FieldDescriptor[] => [
    createTextFilterField(
        'search',
        { key: 'column.search', fallback: 'Search' },
        {
            key: 'page.asset_maintenances.search_placeholder',
            fallback: 'Search maintenances...',
        },
    ),
    ...createAssetSupplierFilterFields(
        { key: 'filter.filter_by_asset', fallback: 'Filter by asset' },
        { key: 'filter.filter_by_supplier', fallback: 'Filter by supplier' },
    ),
    ...createMaintenanceTypeStatusFilterFields(
        { key: 'filter.filter_by_type', fallback: 'Filter by type' },
        { key: 'filter.filter_by_status', fallback: 'Filter by status' },
    ),
    ...createDateFilterFields([
        {
            name: 'scheduled_from',
            label: { key: 'column.scheduled_from', fallback: 'Scheduled From' },
            placeholder: {
                key: 'column.scheduled_from',
                fallback: 'Scheduled From',
            },
        },
        {
            name: 'scheduled_to',
            label: { key: 'column.scheduled_to', fallback: 'Scheduled To' },
            placeholder: {
                key: 'column.scheduled_to',
                fallback: 'Scheduled To',
            },
        },
        {
            name: 'performed_from',
            label: { key: 'column.performed_from', fallback: 'Performed From' },
            placeholder: {
                key: 'column.performed_from',
                fallback: 'Performed From',
            },
        },
        {
            name: 'performed_to',
            label: { key: 'column.performed_to', fallback: 'Performed To' },
            placeholder: {
                key: 'column.performed_to',
                fallback: 'Performed To',
            },
        },
    ]),
    ...createCostRangeFilterFields(),
];
