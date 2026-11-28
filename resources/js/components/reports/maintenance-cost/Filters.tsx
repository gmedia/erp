import {
    createAssetReportScopeFilterFields,
    createDateRangeFilterFields,
    createMaintenanceTypeStatusFilterFields,
    createSupplierFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createMaintenanceCostReportFilterFields(): FieldDescriptor[] {
    return [
        ...createAssetReportScopeFilterFields({
            key: 'page.maintenance_cost.search_placeholder',
            fallback: 'Search code, name, notes...',
        }),
        createSupplierFilterField(
            { key: 'filter.select_vendor', fallback: 'Select a vendor' },
            { key: 'column.vendor', fallback: 'Vendor' },
        ),
        ...createMaintenanceTypeStatusFilterFields(),
        ...createDateRangeFilterFields(),
    ];
}
