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
        'Search maintenances...',
    ),
    ...createAssetSupplierFilterFields('Filter by asset', 'Filter by supplier'),
    ...createMaintenanceTypeStatusFilterFields(
        'Filter by type',
        'Filter by status',
    ),
    ...createDateFilterFields([
        {
            name: 'scheduled_from',
            label: { key: 'column.scheduled_from', fallback: 'Scheduled From' },
            placeholder: 'Scheduled From',
        },
        {
            name: 'scheduled_to',
            label: { key: 'column.scheduled_to', fallback: 'Scheduled To' },
            placeholder: 'Scheduled To',
        },
        {
            name: 'performed_from',
            label: { key: 'column.performed_from', fallback: 'Performed From' },
            placeholder: 'Performed From',
        },
        {
            name: 'performed_to',
            label: { key: 'column.performed_to', fallback: 'Performed To' },
            placeholder: 'Performed To',
        },
    ]),
    ...createCostRangeFilterFields(),
];
