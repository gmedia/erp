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

export function createAsyncSelectFilterFields(
    configs: AsyncSelectFilterConfig[],
): FieldDescriptor[] {
    return configs.map(({ name, label, url, placeholder }) =>
        createAsyncSelectFilterField(name, label, url, placeholder),
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
