import {
    createAssetCategoryBranchFilterFields,
    createDateRangeFilterFields,
    createMaintenanceTypeStatusFilterFields,
    createSupplierFilterField,
    createTextFilterField,
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
        ...createMaintenanceTypeStatusFilterFields(),
        ...createDateRangeFilterFields(),
    ];
}
