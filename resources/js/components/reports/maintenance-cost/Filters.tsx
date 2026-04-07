import {
    createAssetReportScopeFilterFields,
    createDateRangeFilterFields,
    createMaintenanceTypeStatusFilterFields,
    createSupplierFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createMaintenanceCostReportFilterFields(): FieldDescriptor[] {
    return [
        ...createAssetReportScopeFilterFields('Search code, name, notes...'),
        createSupplierFilterField('Select a vendor', 'Vendor'),
        ...createMaintenanceTypeStatusFilterFields(),
        ...createDateRangeFilterFields(),
    ];
}
