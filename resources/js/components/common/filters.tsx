'use client';

import { AsyncSelect } from '@/components/common/AsyncSelect';
import { FilterDatePicker } from '@/components/common/FilterDatePicker';

import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import * as React from 'react';

export type FieldDescriptor = {
    name: string;
    label: string;
    component: React.ReactNode;
};

export type SelectOption = {
    value: string;
    label: string;
};

export const purchaseOrderStatusOptions: SelectOption[] = [
    { value: 'draft', label: 'Draft' },
    { value: 'pending_approval', label: 'Pending Approval' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'partially_received', label: 'Partially Received' },
    { value: 'fully_received', label: 'Fully Received' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'closed', label: 'Closed' },
];

export const maintenanceTypeOptions: SelectOption[] = [
    { value: 'preventive', label: 'Preventive' },
    { value: 'corrective', label: 'Corrective' },
    { value: 'calibration', label: 'Calibration' },
    { value: 'other', label: 'Other' },
];

export const maintenanceStatusOptions: SelectOption[] = [
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

export const assetStatusOptions: SelectOption[] = [
    { value: 'draft', label: 'Draft' },
    { value: 'active', label: 'Active' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'disposed', label: 'Disposed' },
    { value: 'lost', label: 'Lost' },
];

export const assetConditionOptions: SelectOption[] = [
    { value: 'good', label: 'Good' },
    { value: 'needs_repair', label: 'Needs Repair' },
    { value: 'damaged', label: 'Damaged' },
];

export const stockAdjustmentTypeOptions: SelectOption[] = [
    { value: 'damage', label: 'Damage' },
    { value: 'expired', label: 'Expired' },
    { value: 'shrinkage', label: 'Shrinkage' },
    { value: 'correction', label: 'Correction' },
    { value: 'stocktake_result', label: 'Stocktake Result' },
    { value: 'initial_stock', label: 'Initial Stock' },
    { value: 'other', label: 'Other' },
];

export const stockAdjustmentStatusOptions: SelectOption[] = [
    { value: 'draft', label: 'Draft' },
    { value: 'pending_approval', label: 'Pending Approval' },
    { value: 'approved', label: 'Approved' },
    { value: 'cancelled', label: 'Cancelled' },
];

export const assetStocktakeVarianceResultOptions: SelectOption[] = [
    { value: 'damaged', label: 'Damaged' },
    { value: 'missing', label: 'Missing' },
    { value: 'moved', label: 'Moved' },
];

export type AsyncSelectFilterConfig = {
    name: string;
    label: string;
    url: string;
    placeholder: string;
};

export type SelectFilterConfig = {
    name: string;
    label: string;
    options: SelectOption[];
    placeholder: string;
};

export type DateFilterConfig = {
    name: string;
    label: string;
    placeholder: string;
};

// Generic filter fields for simple entities
export function createSimpleEntityFilterFields(
    placeholder: string,
): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            component: <Input placeholder={placeholder} />,
        },
    ];
}

// Generic select filter field creator
export function createSelectFilterField(
    name: string,
    label: string,
    options: SelectOption[],
    placeholder: string,
): FieldDescriptor {
    return {
        name,
        label,
        component: (
            <Select>
                <SelectTrigger className="border-border bg-background">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent className="border-border bg-background text-foreground">
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        ),
    };
}

// Generic text input filter field creator
export function createTextFilterField(
    name: string,
    label: string,
    placeholder: string,
): FieldDescriptor {
    return {
        name,
        label,
        component: <Input placeholder={placeholder} />,
    };
}

// Generic async select filter field creator
export function createAsyncSelectFilterField(
    name: string,
    label: string,
    url: string,
    placeholder: string,
): FieldDescriptor {
    return {
        name,
        label,
        component: <AsyncSelect url={url} placeholder={placeholder} />,
    };
}

export function createUserFilterField(
    name: string,
    label: string,
    placeholder = 'Select a user',
): FieldDescriptor {
    return createAsyncSelectFilterField(name, label, '/api/users', placeholder);
}

export function createAsyncSelectFilterFields(
    configs: AsyncSelectFilterConfig[],
): FieldDescriptor[] {
    return configs.map(({ name, label, url, placeholder }) =>
        createAsyncSelectFilterField(name, label, url, placeholder),
    );
}

export function createBranchFilterField(
    placeholder = 'All branches',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'branch_id',
        'Branch',
        '/api/branches',
        placeholder,
    );
}

export function createWarehouseFilterField(
    placeholder = 'All warehouses',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'warehouse_id',
        'Warehouse',
        '/api/warehouses',
        placeholder,
    );
}

export function createSupplierFilterField(
    placeholder = 'All suppliers',
    label = 'Supplier',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'supplier_id',
        label,
        '/api/suppliers',
        placeholder,
    );
}

export function createAssetFilterField(
    placeholder = 'Select an asset',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'asset_id',
        'Asset',
        '/api/assets',
        placeholder,
    );
}

export function createAssetCategoryFilterField(
    placeholder = 'Select a category',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'asset_category_id',
        'Category',
        '/api/asset-categories',
        placeholder,
    );
}

export function createAssetStocktakeFilterField(
    placeholder = 'Select a stocktake',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'asset_stocktake_id',
        'Stocktake',
        '/api/asset-stocktakes',
        placeholder,
    );
}

export function createSupplierWarehouseProductFilterFields(): FieldDescriptor[] {
    return createAsyncSelectFilterFields([
        {
            name: 'supplier_id',
            label: 'Supplier',
            url: '/api/suppliers',
            placeholder: 'All suppliers',
        },
        {
            name: 'warehouse_id',
            label: 'Warehouse',
            url: '/api/warehouses',
            placeholder: 'All warehouses',
        },
        {
            name: 'product_id',
            label: 'Product',
            url: '/api/products',
            placeholder: 'All products',
        },
    ]);
}

export function createAssetCategoryBranchFilterFields(): FieldDescriptor[] {
    return [
        createAssetCategoryFilterField('Select a category'),
        createBranchFilterField('Select a branch'),
    ];
}

export function createAssetSupplierFilterFields(
    assetPlaceholder = 'Select an asset',
    supplierPlaceholder = 'Select a supplier',
): FieldDescriptor[] {
    return [
        createAssetFilterField(assetPlaceholder),
        createSupplierFilterField(supplierPlaceholder),
    ];
}

export function createMaintenanceTypeStatusFilterFields(
    typePlaceholder = 'Select type',
    statusPlaceholder = 'Select status',
): FieldDescriptor[] {
    return [
        createSelectFilterField(
            'maintenance_type',
            'Type',
            maintenanceTypeOptions,
            typePlaceholder,
        ),
        createSelectFilterField(
            'status',
            'Status',
            maintenanceStatusOptions,
            statusPlaceholder,
        ),
    ];
}

export function createAssetStatusConditionFilterFields(
    statusPlaceholder = 'Select a status',
    conditionPlaceholder = 'Select a condition',
): FieldDescriptor[] {
    return [
        createSelectFilterField(
            'status',
            'Status',
            assetStatusOptions,
            statusPlaceholder,
        ),
        createSelectFilterField(
            'condition',
            'Condition',
            assetConditionOptions,
            conditionPlaceholder,
        ),
    ];
}

export function createWarehouseBranchFilterFields(): FieldDescriptor[] {
    return [
        createWarehouseFilterField('All warehouses'),
        createBranchFilterField('All branches'),
    ];
}

export function createProductWarehouseBranchCategoryFilterFields(): FieldDescriptor[] {
    return createAsyncSelectFilterFields([
        {
            name: 'product_id',
            label: 'Product',
            url: '/api/products',
            placeholder: 'All products',
        },
        {
            name: 'warehouse_id',
            label: 'Warehouse',
            url: '/api/warehouses',
            placeholder: 'All warehouses',
        },
        {
            name: 'branch_id',
            label: 'Branch',
            url: '/api/branches',
            placeholder: 'All branches',
        },
        {
            name: 'category_id',
            label: 'Category',
            url: '/api/product-categories',
            placeholder: 'All categories',
        },
    ]);
}

export function createSelectFilterFields(
    configs: SelectFilterConfig[],
): FieldDescriptor[] {
    return configs.map(({ name, label, options, placeholder }) =>
        createSelectFilterField(name, label, options, placeholder),
    );
}

export function createDateFilterFields(
    configs: DateFilterConfig[],
): FieldDescriptor[] {
    return configs.map(({ name, label, placeholder }) => ({
        name,
        label,
        component: <FilterDatePicker placeholder={placeholder} />,
    }));
}

// Shared date-range fields used by report filters.
export function createDateRangeFilterFields(
    startPlaceholder = 'Start Date',
    endPlaceholder = 'End Date',
): FieldDescriptor[] {
    return [
        {
            name: 'start_date',
            label: 'Start Date',
            component: <FilterDatePicker placeholder={startPlaceholder} />,
        },
        {
            name: 'end_date',
            label: 'End Date',
            component: <FilterDatePicker placeholder={endPlaceholder} />,
        },
    ];
}

export function createCostRangeFilterFields(
    minPlaceholder = '0',
    maxPlaceholder = '0',
): FieldDescriptor[] {
    return [
        {
            name: 'cost_min',
            label: 'Min Cost',
            component: (
                <Input type="number" min={0} placeholder={minPlaceholder} />
            ),
        },
        {
            name: 'cost_max',
            label: 'Max Cost',
            component: (
                <Input type="number" min={0} placeholder={maxPlaceholder} />
            ),
        },
    ];
}
