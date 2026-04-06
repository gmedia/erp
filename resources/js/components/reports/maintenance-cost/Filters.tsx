import {
    createAssetCategoryBranchFilterFields,
    createDateRangeFilterFields,
    createSelectFilterField,
    createSupplierFilterField,
    createTextFilterField,
    maintenanceStatusOptions,
    maintenanceTypeOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createMaintenanceCostReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search code, name, notes...',
        ),
        ...createAssetCategoryBranchFilterFields(),
        createSupplierFilterField('Select a vendor', 'Vendor'),
        createSelectFilterField(
            'maintenance_type',
            'Type',
            maintenanceTypeOptions,
            'Select type',
        ),
        createSelectFilterField(
            'status',
            'Status',
            maintenanceStatusOptions,
            'Select status',
        ),
        ...createDateRangeFilterFields(),
    ];
}
