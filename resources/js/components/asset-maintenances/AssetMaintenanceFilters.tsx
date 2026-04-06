import {
    type FieldDescriptor,
    createAssetSupplierFilterFields,
    createCostRangeFilterFields,
    createDateFilterFields,
    createMaintenanceTypeStatusFilterFields,
    createTextFilterField,
} from '@/components/common/filters';

export const createAssetMaintenanceFilterFields = (): FieldDescriptor[] => [
    createTextFilterField('search', 'Search', 'Search maintenances...'),
    ...createAssetSupplierFilterFields('Filter by asset', 'Filter by supplier'),
    ...createMaintenanceTypeStatusFilterFields(
        'Filter by type',
        'Filter by status',
    ),
    ...createDateFilterFields([
        {
            name: 'scheduled_from',
            label: 'Scheduled From',
            placeholder: 'Scheduled From',
        },
        {
            name: 'scheduled_to',
            label: 'Scheduled To',
            placeholder: 'Scheduled To',
        },
        {
            name: 'performed_from',
            label: 'Performed From',
            placeholder: 'Performed From',
        },
        {
            name: 'performed_to',
            label: 'Performed To',
            placeholder: 'Performed To',
        },
    ]),
    ...createCostRangeFilterFields(),
];
