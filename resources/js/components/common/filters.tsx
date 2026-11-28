'use client';

import {
    AsyncSelect,
    type AsyncSelectProps,
} from '@/components/common/AsyncSelect';
import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    LabelText,
    type TranslatableLabel,
    useTranslatableText,
} from '@/components/common/TranslatedText';
import { useTranslation } from '@/contexts/i18n-context';

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
    label: TranslatableLabel;
    component: React.ReactNode;
};

export type SelectOption = {
    value: string;
    label: TranslatableLabel;
};

type OptionEntry = readonly [value: string, fallback: string, key?: string];

// Builds translatable select options. The translation key is `${keyPrefix}${value}`
// unless the entry supplies an explicit key as its third element.
export function translatedOptions(
    keyPrefix: string,
    entries: ReadonlyArray<OptionEntry>,
): SelectOption[] {
    return entries.map(([value, fallback, key]) => ({
        value,
        label: { key: key ?? `${keyPrefix}${value}`, fallback },
    }));
}

export const purchaseOrderStatusOptions = translatedOptions('status.', [
    ['draft', 'Draft'],
    ['pending_approval', 'Pending Approval'],
    ['confirmed', 'Confirmed'],
    ['rejected', 'Rejected'],
    ['partially_received', 'Partially Received'],
    ['fully_received', 'Fully Received'],
    ['cancelled', 'Cancelled'],
    ['closed', 'Closed'],
]);

export const currencyOptions: SelectOption[] = [
    { value: 'IDR', label: 'IDR' },
    { value: 'USD', label: 'USD' },
    { value: 'EUR', label: 'EUR' },
];

export const purchaseRequestPriorityOptions = translatedOptions('status.', [
    ['low', 'Low'],
    ['normal', 'Normal'],
    ['high', 'High'],
    ['urgent', 'Urgent'],
]);

export const purchaseRequestStatusOptions = translatedOptions('status.', [
    ['draft', 'Draft'],
    ['pending_approval', 'Pending Approval'],
    ['approved', 'Approved'],
    ['rejected', 'Rejected'],
    ['partially_ordered', 'Partially Ordered'],
    ['fully_ordered', 'Fully Ordered'],
    ['cancelled', 'Cancelled'],
]);

export const apBillStatusOptions = translatedOptions('status.', [
    ['confirmed', 'Confirmed'],
    ['partially_paid', 'Partially Paid'],
    ['overdue', 'Overdue'],
]);

export const purchaseOrderStatusCategoryOptions = translatedOptions('status.', [
    ['outstanding', 'Outstanding'],
    ['partially_received', 'Partially Received'],
    ['closed', 'Closed'],
]);

export const fiscalYearStatusOptions = translatedOptions('status.', [
    ['open', 'Open'],
    ['closed', 'Closed'],
    ['locked', 'Locked'],
]);

export const coaVersionStatusOptions = translatedOptions('status.', [
    ['draft', 'Draft'],
    ['active', 'Active'],
    ['archived', 'Archived'],
]);

export const journalEntryStatusOptions = translatedOptions('status.', [
    ['draft', 'Draft'],
    ['posted', 'Posted'],
    ['void', 'Void'],
]);

export const approvalAuditEventOptions = translatedOptions(
    'filter.approval_event_',
    [
        ['submitted', 'Submitted'],
        ['step_approved', 'Step Approved'],
        ['step_rejected', 'Step Rejected', 'status.step_rejected'],
        ['step_skipped', 'Step Skipped'],
        ['auto_approved', 'Auto Approved', 'status.auto_approved'],
        ['escalated', 'Escalated'],
        ['delegated', 'Delegated'],
        ['cancelled', 'Cancelled', 'status.cancelled'],
        ['resubmitted', 'Resubmitted'],
        ['completed', 'Completed', 'status.completed'],
    ],
);

export const securityLogEventOptions = translatedOptions(
    'filter.security_event_',
    [
        ['login_success', 'Login Success'],
        ['login_failed', 'Login Failed'],
        ['logout', 'Logout'],
        ['password_reset', 'Password Reset'],
        ['role_changed', 'Role Changed'],
        ['user_created', 'User Created'],
        ['user_deactivated', 'User Deactivated'],
    ],
);

export const maintenanceTypeOptions = translatedOptions(
    'form_fields.asset_maintenances.maintenance_type_',
    [
        ['preventive', 'Preventive'],
        ['corrective', 'Corrective'],
        ['calibration', 'Calibration'],
        ['other', 'Other'],
    ],
);

export const maintenanceStatusOptions = translatedOptions('status.', [
    ['scheduled', 'Scheduled'],
    ['in_progress', 'In Progress'],
    ['completed', 'Completed'],
    ['cancelled', 'Cancelled'],
]);

export const assetStatusOptions = translatedOptions('status.', [
    ['draft', 'Draft'],
    ['active', 'Active'],
    ['maintenance', 'Maintenance'],
    ['disposed', 'Disposed'],
    ['lost', 'Lost'],
]);

export const assetConditionOptions = translatedOptions(
    'form_fields.assets.condition_',
    [
        ['good', 'Good'],
        ['needs_repair', 'Needs Repair'],
        ['damaged', 'Damaged', 'status.damaged'],
    ],
);

export const activeInactiveStatusOptions = translatedOptions('status.', [
    ['active', 'Active'],
    ['inactive', 'Inactive'],
]);

export const employmentStatusOptions = translatedOptions('status.', [
    ['regular', 'Regular'],
    ['intern', 'Intern'],
]);

export const productTypeOptions = translatedOptions(
    'form_fields.products.type_',
    [
        ['raw_material', 'Raw Material'],
        ['work_in_progress', 'WIP'],
        ['finished_good', 'Finished Good'],
        ['purchased_good', 'Purchased Good'],
        ['service', 'Service'],
    ],
);

export const productStatusOptions = translatedOptions('status.', [
    ['active', 'Active'],
    ['inactive', 'Inactive'],
    ['discontinued', 'Discontinued'],
]);

export const inventoryStocktakeStatusOptions = translatedOptions('status.', [
    ['draft', 'Draft'],
    ['in_progress', 'In Progress'],
    ['completed', 'Completed'],
    ['cancelled', 'Cancelled'],
]);

export const inventoryStocktakeVarianceResultOptions = translatedOptions(
    'status.',
    [
        ['surplus', 'Surplus'],
        ['deficit', 'Deficit'],
    ],
);

export const draftConfirmedCancelledStatusOptions = translatedOptions(
    'status.',
    [
        ['draft', 'Draft'],
        ['confirmed', 'Confirmed'],
        ['cancelled', 'Cancelled'],
    ],
);

export const approvalWorkflowStatusOptions = translatedOptions('status.', [
    ['draft', 'Draft'],
    ['pending_approval', 'Pending Approval'],
    ['approved', 'Approved'],
    ['cancelled', 'Cancelled'],
]);

export const stockTransferStatusOptions = translatedOptions('status.', [
    ['draft', 'Draft'],
    ['pending_approval', 'Pending Approval'],
    ['approved', 'Approved'],
    ['in_transit', 'In Transit'],
    ['received', 'Received'],
    ['cancelled', 'Cancelled'],
]);

export const supplierReturnReasonOptions = translatedOptions(
    'form_fields.supplier_returns.reason_',
    [
        ['defective', 'Defective'],
        ['wrong_item', 'Wrong Item'],
        ['excess_quantity', 'Excess Quantity'],
        ['damaged', 'Damaged', 'status.damaged'],
        ['other', 'Other'],
    ],
);

export const stockAdjustmentTypeOptions = translatedOptions(
    'form_fields.stock_adjustments.adjustment_type_',
    [
        ['damage', 'Damage'],
        ['expired', 'Expired'],
        ['shrinkage', 'Shrinkage'],
        ['correction', 'Correction'],
        ['stocktake_result', 'Stocktake Result'],
        ['initial_stock', 'Initial Stock'],
        ['other', 'Other'],
    ],
);

export const stockAdjustmentStatusOptions = translatedOptions('status.', [
    ['draft', 'Draft'],
    ['pending_approval', 'Pending Approval'],
    ['approved', 'Approved'],
    ['cancelled', 'Cancelled'],
]);

export const stockMovementTypeOptions = translatedOptions('filter.movement_', [
    ['goods_receipt', 'Goods Receipt'],
    ['supplier_return', 'Supplier Return'],
    ['transfer_out', 'Transfer Out'],
    ['transfer_in', 'Transfer In'],
    ['adjustment_in', 'Adjustment In'],
    ['adjustment_out', 'Adjustment Out'],
    ['production_consume', 'Production Consume'],
    ['production_output', 'Production Output'],
    ['sales', 'Sales'],
    ['sales_return', 'Sales Return'],
]);

export const assetStocktakeVarianceResultOptions = translatedOptions(
    'status.',
    [
        ['damaged', 'Damaged'],
        ['missing', 'Missing'],
        ['moved', 'Moved'],
    ],
);

export type AsyncSelectFilterConfig = {
    name: string;
    label: TranslatableLabel;
    url: string;
    placeholder: TranslatableLabel;
};

export type SelectFilterConfig = {
    name: string;
    label: TranslatableLabel;
    options: SelectOption[];
    placeholder: TranslatableLabel;
};

export type DateFilterConfig = {
    name: string;
    label: TranslatableLabel;
    placeholder: TranslatableLabel;
};

// Generic filter fields for simple entities
export function createSimpleEntityFilterFields(
    placeholder: TranslatableLabel,
): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: { key: 'filter_field.search', fallback: 'Search' },
            component: <TranslatedPlaceholderInput placeholder={placeholder} />,
        },
    ];
}

// Generic select filter field creator
export function createSelectFilterField(
    name: string,
    label: TranslatableLabel,
    options: SelectOption[],
    placeholder: TranslatableLabel,
): FieldDescriptor {
    return {
        name,
        label,
        component: (
            <Select>
                <SelectTrigger>
                    <SelectValue
                        placeholder={<LabelText label={placeholder} />}
                    />
                </SelectTrigger>
                <SelectContent className="border-border bg-background text-foreground">
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            <LabelText label={option.label} />
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        ),
    };
}

// Generic text input filter field creator
export function TranslatedPlaceholderInput({
    placeholder,
    ...rest
}: Readonly<
    Omit<React.ComponentProps<typeof Input>, 'placeholder'> & {
        placeholder: TranslatableLabel;
    }
>) {
    const { t } = useTranslation();
    if (typeof placeholder === 'string') {
        return <Input placeholder={placeholder} {...rest} />;
    }
    const translated = t(placeholder.key);
    return (
        <Input
            {...rest}
            placeholder={
                translated === placeholder.key
                    ? placeholder.fallback
                    : translated
            }
        />
    );
}

export function createTextFilterField(
    name: string,
    label: TranslatableLabel,
    placeholder: TranslatableLabel,
): FieldDescriptor {
    return {
        name,
        label,
        component: <TranslatedPlaceholderInput placeholder={placeholder} />,
    };
}

export function TranslatedAsyncSelect({
    placeholder,
    ...rest
}: Readonly<
    Omit<AsyncSelectProps, 'placeholder'> & { placeholder: TranslatableLabel }
>) {
    const translatedPlaceholder = useTranslatableText(placeholder);
    return <AsyncSelect {...rest} placeholder={translatedPlaceholder} />;
}
TranslatedAsyncSelect.displayName = 'AsyncSelect';

export function createAsyncSelectFilterField(
    name: string,
    label: TranslatableLabel,
    url: string,
    placeholder: TranslatableLabel,
): FieldDescriptor {
    return {
        name,
        label,
        component: (
            <TranslatedAsyncSelect url={url} placeholder={placeholder} />
        ),
    };
}

export function createUserFilterField(
    name: string,
    label: TranslatableLabel,
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_user',
        fallback: 'Select a user',
    },
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

export type AsyncLookupFilterTuple = readonly [
    name: string,
    entity: string,
    url: string,
];

// Builds async select filters whose label/placeholder follow the shared
// `column.<entity>` / `filter.select_<entity>` convention.
export function createAsyncLookupFilterFields(
    entries: ReadonlyArray<AsyncLookupFilterTuple>,
): FieldDescriptor[] {
    return entries.map(([name, entity, url]) => {
        const labelFallback = entity.charAt(0).toUpperCase() + entity.slice(1);
        const article = /^[aeiou]/i.test(entity) ? 'an' : 'a';
        return createAsyncSelectFilterField(
            name,
            { key: `column.${entity}`, fallback: labelFallback },
            url,
            {
                key: `filter.select_${entity}`,
                fallback: `Select ${article} ${entity}`,
            },
        );
    });
}

export function createBranchFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter.all_branches',
        fallback: 'All branches',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'branch_id',
        { key: 'column.branch', fallback: 'Branch' },
        '/api/branches',
        placeholder,
    );
}

export function createWarehouseFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter.all_warehouses',
        fallback: 'All warehouses',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'warehouse_id',
        { key: 'column.warehouse', fallback: 'Warehouse' },
        '/api/warehouses',
        placeholder,
    );
}

export function createUnitFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter.select_unit',
        fallback: 'Select unit',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'unit_id',
        { key: 'column.unit', fallback: 'Unit' },
        '/api/units',
        placeholder,
    );
}

export function createDepartmentFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter.select_department',
        fallback: 'Select a department',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'department_id',
        { key: 'column.department', fallback: 'Department' },
        '/api/departments',
        placeholder,
    );
}

export function createPositionFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter.select_position',
        fallback: 'Select a position',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'position_id',
        { key: 'column.position', fallback: 'Position' },
        '/api/positions',
        placeholder,
    );
}

export function createSupplierFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.all_suppliers',
        fallback: 'All suppliers',
    },
    label: TranslatableLabel = {
        key: 'filter_field.supplier',
        fallback: 'Supplier',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'supplier_id',
        label,
        '/api/suppliers',
        placeholder,
    );
}

export function createProductFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_product',
        fallback: 'Select a product',
    },
    label: TranslatableLabel = {
        key: 'filter_field.product',
        fallback: 'Product',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'product_id',
        label,
        '/api/products',
        placeholder,
    );
}

export function createEmployeeLookupFilterField(
    name = 'employee_id',
    label: TranslatableLabel = {
        key: 'filter_field.employee',
        fallback: 'Employee',
    },
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_employee',
        fallback: 'Select employee',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        name,
        label,
        '/api/employees',
        placeholder,
    );
}

export function createPurchaseOrderFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_purchase_order',
        fallback: 'Select purchase order',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'purchase_order_id',
        { key: 'filter_field.purchase_order', fallback: 'Purchase Order' },
        '/api/purchase-orders',
        placeholder,
    );
}

export function createGoodsReceiptFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_goods_receipt',
        fallback: 'Select goods receipt',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'goods_receipt_id',
        { key: 'filter_field.goods_receipt', fallback: 'Goods Receipt' },
        '/api/goods-receipts',
        placeholder,
    );
}

export function createAssetFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_asset',
        fallback: 'Select an asset',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'asset_id',
        { key: 'filter_field.asset', fallback: 'Asset' },
        '/api/assets',
        placeholder,
    );
}

export function createAssetCategoryFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter.select_category',
        fallback: 'Select a category',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'asset_category_id',
        { key: 'filter_field.category', fallback: 'Category' },
        '/api/asset-categories',
        placeholder,
    );
}

export function createAssetStocktakeFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_stocktake',
        fallback: 'Select a stocktake',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'asset_stocktake_id',
        { key: 'filter_field.stocktake', fallback: 'Stocktake' },
        '/api/asset-stocktakes',
        placeholder,
    );
}

export function createCustomerCategoryFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter.select_category_party',
        fallback: 'Select Category',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'category_id',
        { key: 'column.category', fallback: 'Category' },
        '/api/customer-categories',
        placeholder,
    );
}

export function createSupplierCategoryFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter.select_category_party',
        fallback: 'Select Category',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'category_id',
        { key: 'column.category', fallback: 'Category' },
        '/api/supplier-categories',
        placeholder,
    );
}

export function createProductCategoryFilterField(
    name = 'product_category_id',
    label: TranslatableLabel = { key: 'column.category', fallback: 'Category' },
    placeholder: TranslatableLabel = {
        key: 'filter.select_category',
        fallback: 'Select category',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        name,
        label,
        '/api/product-categories',
        placeholder,
    );
}

export function createSupplierWarehouseProductFilterFields(): FieldDescriptor[] {
    return createAsyncSelectFilterFields([
        {
            name: 'supplier_id',
            label: { key: 'filter_field.supplier', fallback: 'Supplier' },
            url: '/api/suppliers',
            placeholder: {
                key: 'filter_field.all_suppliers',
                fallback: 'All suppliers',
            },
        },
        {
            name: 'warehouse_id',
            label: { key: 'filter_field.warehouse', fallback: 'Warehouse' },
            url: '/api/warehouses',
            placeholder: {
                key: 'filter_field.all_warehouses',
                fallback: 'All warehouses',
            },
        },
        {
            name: 'product_id',
            label: { key: 'filter_field.product', fallback: 'Product' },
            url: '/api/products',
            placeholder: {
                key: 'filter_field.all_products',
                fallback: 'All products',
            },
        },
    ]);
}

export function createPurchasingReportScopeFilterFields(): FieldDescriptor[] {
    return [
        ...createSupplierWarehouseProductFilterFields(),
        ...createDateRangeFilterFields(),
    ];
}

export function createGoodsReceiptStatusFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.all_statuses',
        fallback: 'All statuses',
    },
): FieldDescriptor {
    return createSelectFilterField(
        'status',
        { key: 'filter_field.status', fallback: 'Status' },
        draftConfirmedCancelledStatusOptions,
        placeholder,
    );
}

export function createPurchaseOrderStatusFilterField(
    name = 'status',
    label: TranslatableLabel = {
        key: 'filter_field.po_status',
        fallback: 'PO Status',
    },
    placeholder: TranslatableLabel = {
        key: 'filter_field.all_statuses',
        fallback: 'All statuses',
    },
): FieldDescriptor {
    return createSelectFilterField(
        name,
        label,
        purchaseOrderStatusOptions,
        placeholder,
    );
}

export function createPurchaseOrderStatusCategoryFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.all_categories',
        fallback: 'All categories',
    },
): FieldDescriptor {
    return createSelectFilterField(
        'status_category',
        { key: 'filter_field.status_category', fallback: 'Status Category' },
        purchaseOrderStatusCategoryOptions,
        placeholder,
    );
}

export function createBinaryStatusFilterField(
    name = 'status',
    label: TranslatableLabel = {
        key: 'filter_field.status',
        fallback: 'Status',
    },
    activeValue = 'active',
    inactiveValue = 'inactive',
    placeholder: TranslatableLabel = {
        key: 'filter_field.all_statuses',
        fallback: 'All Statuses',
    },
    activeLabel: TranslatableLabel = {
        key: 'status.active',
        fallback: 'Active',
    },
    inactiveLabel: TranslatableLabel = {
        key: 'status.inactive',
        fallback: 'Inactive',
    },
): FieldDescriptor {
    return createSelectFilterField(
        name,
        label,
        [
            { label: activeLabel, value: activeValue },
            { label: inactiveLabel, value: inactiveValue },
        ],
        placeholder,
    );
}

export function createFiscalYearStatusFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.all_statuses',
        fallback: 'All Statuses',
    },
): FieldDescriptor {
    return createSelectFilterField(
        'status',
        { key: 'filter_field.status', fallback: 'Status' },
        fiscalYearStatusOptions,
        placeholder,
    );
}

export function createCoaVersionStatusFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.all_statuses',
        fallback: 'All Statuses',
    },
): FieldDescriptor {
    return createSelectFilterField(
        'status',
        { key: 'filter_field.status', fallback: 'Status' },
        coaVersionStatusOptions,
        placeholder,
    );
}

export function createJournalEntryStatusFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_status',
        fallback: 'Select status',
    },
): FieldDescriptor {
    return createSelectFilterField(
        'status',
        { key: 'filter_field.status', fallback: 'Status' },
        journalEntryStatusOptions,
        placeholder,
    );
}

export function createApprovalAuditEventFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_event',
        fallback: 'Select Event',
    },
): FieldDescriptor {
    return createSelectFilterField(
        'event',
        { key: 'filter_field.event', fallback: 'Event' },
        approvalAuditEventOptions,
        placeholder,
    );
}

export function createSecurityLogEventFilterField(
    placeholder: TranslatableLabel = {
        key: 'page.security_logs.select_event',
        fallback: 'Select Event',
    },
): FieldDescriptor {
    return createSelectFilterField(
        'event',
        { key: 'page.security_logs.column_event', fallback: 'Event' },
        securityLogEventOptions,
        placeholder,
    );
}

export function createPipelineFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.select_pipeline',
        fallback: 'Select a pipeline',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'pipeline_id',
        { key: 'filter_field.pipeline', fallback: 'Pipeline' },
        '/api/pipelines',
        placeholder,
    );
}

export function createApprovalDelegationUserFilterFields(): FieldDescriptor[] {
    return [
        createUserFilterField(
            'delegator_user_id',
            { key: 'filter_field.delegator', fallback: 'Delegator' },
            {
                key: 'filter_field.select_delegator',
                fallback: 'Select delegator',
            },
        ),
        createUserFilterField(
            'delegate_user_id',
            { key: 'filter_field.delegate', fallback: 'Delegate' },
            {
                key: 'filter_field.select_delegate',
                fallback: 'Select delegate',
            },
        ),
    ];
}

export function createSupplierWarehouseFilterFields(
    supplierPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_supplier',
        fallback: 'Select a supplier',
    },
    warehousePlaceholder: TranslatableLabel = {
        key: 'filter_field.select_warehouse',
        fallback: 'Select a warehouse',
    },
): FieldDescriptor[] {
    return [
        createSupplierFilterField(supplierPlaceholder),
        createWarehouseFilterField(warehousePlaceholder),
    ];
}

export function createPurchaseRequestContextFilterFields(
    requesterPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_requester',
        fallback: 'Select requester',
    },
): FieldDescriptor[] {
    return [
        createBranchFilterField({
            key: 'filter_field.select_branch',
            fallback: 'Select a branch',
        }),
        createDepartmentFilterField({
            key: 'filter_field.select_department',
            fallback: 'Select a department',
        }),
        createEmployeeLookupFilterField(
            'requested_by',
            { key: 'filter_field.requester', fallback: 'Requester' },
            requesterPlaceholder,
        ),
    ];
}

export function createProductWarehouseFilterFields(
    productPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_product',
        fallback: 'Select a product',
    },
    warehousePlaceholder: TranslatableLabel = {
        key: 'filter_field.select_warehouse',
        fallback: 'Select a warehouse',
    },
): FieldDescriptor[] {
    return [
        createProductFilterField(productPlaceholder),
        createWarehouseFilterField(warehousePlaceholder),
    ];
}

export function createWarehouseStatusFilterFields(
    statusOptions: SelectOption[],
    warehousePlaceholder: TranslatableLabel = {
        key: 'filter_field.select_warehouse',
        fallback: 'Select warehouse',
    },
    statusPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_status',
        fallback: 'Select status',
    },
): FieldDescriptor[] {
    return [
        createWarehouseFilterField(warehousePlaceholder),
        createSelectFilterField(
            'status',
            { key: 'filter_field.status', fallback: 'Status' },
            statusOptions,
            statusPlaceholder,
        ),
    ];
}

export function createAssetCategoryBranchFilterFields(): FieldDescriptor[] {
    return [
        createAssetCategoryFilterField({
            key: 'filter_field.select_category',
            fallback: 'Select a category',
        }),
        createBranchFilterField({
            key: 'filter_field.select_branch',
            fallback: 'Select a branch',
        }),
    ];
}

export function createAssetReportScopeFilterFields(
    searchPlaceholder: TranslatableLabel = {
        key: 'filter_field.search_code_name',
        fallback: 'Search code, name...',
    },
): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'filter_field.search', fallback: 'Search' },
            searchPlaceholder,
        ),
        ...createAssetCategoryBranchFilterFields(),
    ];
}

export function createAssetStocktakeVarianceScopeFilterFields(
    stocktakePlaceholder: TranslatableLabel = {
        key: 'filter_field.select_stocktake',
        fallback: 'Select a stocktake',
    },
    resultPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_result',
        fallback: 'Select result',
    },
): FieldDescriptor[] {
    return [
        createAssetStocktakeFilterField(stocktakePlaceholder),
        createBranchFilterField({
            key: 'filter_field.select_branch',
            fallback: 'Select a branch',
        }),
        createSelectFilterField(
            'result',
            { key: 'filter_field.result', fallback: 'Result' },
            assetStocktakeVarianceResultOptions,
            resultPlaceholder,
        ),
    ];
}

export function createEmployeeOrganizationFilterFields(): FieldDescriptor[] {
    return [
        createDepartmentFilterField(),
        createPositionFilterField(),
        createBranchFilterField({
            key: 'filter.select_branch',
            fallback: 'Select a branch',
        }),
    ];
}

export function createCustomerFilterSelectFields(): FieldDescriptor[] {
    return [
        createBranchFilterField({
            key: 'filter.select_branch',
            fallback: 'Select a branch',
        }),
        createCustomerCategoryFilterField(),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            activeInactiveStatusOptions,
            { key: 'filter.all_statuses', fallback: 'All statuses' },
        ),
    ];
}

export function createSupplierFilterSelectFields(): FieldDescriptor[] {
    return [
        createBranchFilterField({
            key: 'filter.select_branch',
            fallback: 'Select a branch',
        }),
        createSupplierCategoryFilterField(),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            activeInactiveStatusOptions,
            { key: 'filter.select_status', fallback: 'Select Status' },
        ),
    ];
}

export function createEmploymentStatusFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter.select_status',
        fallback: 'Select status',
    },
): FieldDescriptor {
    return createSelectFilterField(
        'employment_status',
        { key: 'column.status', fallback: 'Status' },
        employmentStatusOptions,
        placeholder,
    );
}

export function createProductCatalogAsyncFilterFields(): FieldDescriptor[] {
    return [
        createProductCategoryFilterField(),
        createUnitFilterField(),
        createBranchFilterField({
            key: 'filter.select_branch',
            fallback: 'Select branch',
        }),
    ];
}

export function createProductTypeStatusFilterFields(): FieldDescriptor[] {
    return [
        createSelectFilterField(
            'type',
            { key: 'column.type', fallback: 'Type' },
            productTypeOptions,
            { key: 'filter.select_type', fallback: 'Select type' },
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            productStatusOptions,
            { key: 'filter.select_status', fallback: 'Select status' },
        ),
    ];
}

export function createInventoryStocktakeFilterSelectFields(): FieldDescriptor[] {
    return [
        createWarehouseFilterField({
            key: 'filter_field.select_warehouse',
            fallback: 'Select warehouse',
        }),
        createProductCategoryFilterField(
            'product_category_id',
            { key: 'column.product_category', fallback: 'Product Category' },
            {
                key: 'filter_field.select_category',
                fallback: 'Select category',
            },
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            inventoryStocktakeStatusOptions,
            { key: 'filter_field.select_status', fallback: 'Select status' },
        ),
    ];
}

export function createAssetSupplierFilterFields(
    assetPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_asset',
        fallback: 'Select an asset',
    },
    supplierPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_supplier',
        fallback: 'Select a supplier',
    },
): FieldDescriptor[] {
    return [
        createAssetFilterField(assetPlaceholder),
        createSupplierFilterField(supplierPlaceholder),
    ];
}

export function createMaintenanceTypeStatusFilterFields(
    typePlaceholder: TranslatableLabel = {
        key: 'filter_field.select_type',
        fallback: 'Select type',
    },
    statusPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_status',
        fallback: 'Select status',
    },
): FieldDescriptor[] {
    return [
        createSelectFilterField(
            'maintenance_type',
            { key: 'filter_field.type', fallback: 'Type' },
            maintenanceTypeOptions,
            typePlaceholder,
        ),
        createSelectFilterField(
            'status',
            { key: 'filter_field.status', fallback: 'Status' },
            maintenanceStatusOptions,
            statusPlaceholder,
        ),
    ];
}

export function createAssetStatusConditionFilterFields(
    statusPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_status',
        fallback: 'Select a status',
    },
    conditionPlaceholder: TranslatableLabel = {
        key: 'filter_field.select_condition',
        fallback: 'Select a condition',
    },
): FieldDescriptor[] {
    return [
        createSelectFilterField(
            'status',
            { key: 'filter_field.status', fallback: 'Status' },
            assetStatusOptions,
            statusPlaceholder,
        ),
        createSelectFilterField(
            'condition',
            { key: 'filter_field.condition', fallback: 'Condition' },
            assetConditionOptions,
            conditionPlaceholder,
        ),
    ];
}

export function createWarehouseBranchFilterFields(): FieldDescriptor[] {
    return [
        createWarehouseFilterField({
            key: 'filter_field.all_warehouses',
            fallback: 'All warehouses',
        }),
        createBranchFilterField({
            key: 'filter_field.all_branches',
            fallback: 'All branches',
        }),
    ];
}

export function createProductWarehouseBranchCategoryFilterFields(): FieldDescriptor[] {
    return createAsyncSelectFilterFields([
        {
            name: 'product_id',
            label: { key: 'filter_field.product', fallback: 'Product' },
            url: '/api/products',
            placeholder: {
                key: 'filter_field.all_products',
                fallback: 'All products',
            },
        },
        {
            name: 'warehouse_id',
            label: { key: 'filter_field.warehouse', fallback: 'Warehouse' },
            url: '/api/warehouses',
            placeholder: {
                key: 'filter_field.all_warehouses',
                fallback: 'All warehouses',
            },
        },
        {
            name: 'branch_id',
            label: { key: 'filter_field.branch', fallback: 'Branch' },
            url: '/api/branches',
            placeholder: {
                key: 'filter_field.all_branches',
                fallback: 'All branches',
            },
        },
        {
            name: 'category_id',
            label: { key: 'filter_field.category', fallback: 'Category' },
            url: '/api/product-categories',
            placeholder: {
                key: 'filter_field.all_categories',
                fallback: 'All categories',
            },
        },
    ]);
}

export function createInventoryReportScopeFilterFields(
    includeDateRange = false,
): FieldDescriptor[] {
    const fields = [...createProductWarehouseBranchCategoryFilterFields()];

    if (includeDateRange) {
        fields.push(...createDateRangeFilterFields());
    }

    return fields;
}

export function createCustomerFinancialFilterFields(
    searchPlaceholder: TranslatableLabel,
): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            searchPlaceholder,
        ),
        createAsyncSelectFilterField(
            'customer_id',
            { key: 'column.customer', fallback: 'Customer' },
            '/api/customers',
            {
                key: 'form_fields.customer_invoices.customer_placeholder',
                fallback: 'Select Customer',
            },
        ),
        createAsyncSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            '/api/branches',
            { key: 'filter.select_branch', fallback: 'Select Branch' },
        ),
    ];
}

export function createInventoryStocktakeReportFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.all_stocktakes',
        fallback: 'All stocktakes',
    },
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'inventory_stocktake_id',
        { key: 'filter_field.stocktake', fallback: 'Stocktake' },
        '/api/inventory-stocktakes',
        placeholder,
    );
}

export function createInventoryStocktakeVarianceResultFilterField(
    placeholder: TranslatableLabel = {
        key: 'filter_field.all_results',
        fallback: 'All results',
    },
): FieldDescriptor {
    return createSelectFilterField(
        'result',
        { key: 'filter_field.result', fallback: 'Result' },
        inventoryStocktakeVarianceResultOptions,
        placeholder,
    );
}

export function createSelectFilterFields(
    configs: SelectFilterConfig[],
): FieldDescriptor[] {
    return configs.map(({ name, label, options, placeholder }) =>
        createSelectFilterField(name, label, options, placeholder),
    );
}

export function createColumnSelectConfig(
    name: string,
    labelFallback: string,
    options: SelectOption[],
    placeholderKey = `filter.select_${name}`,
    placeholderFallback = `Select ${labelFallback}`,
): SelectFilterConfig {
    return {
        name,
        label: { key: `column.${name}`, fallback: labelFallback },
        options,
        placeholder: {
            key: placeholderKey,
            fallback: placeholderFallback,
        },
    };
}

export function createStatusFilterConfig(
    options: SelectOption[],
): SelectFilterConfig {
    return createColumnSelectConfig('status', 'Status', options);
}

export function createCurrencyFilterConfig(): SelectFilterConfig {
    return createColumnSelectConfig('currency', 'Currency', currencyOptions);
}

export function createPaymentMethodFilterConfig(
    options: SelectOption[],
): SelectFilterConfig {
    return createColumnSelectConfig(
        'payment_method',
        'Payment Method',
        options,
    );
}

export function createColumnDateFilterConfig(
    name: string,
    fallback: string,
    placeholder?: TranslatableLabel,
): DateFilterConfig {
    const label: TranslatableLabel = { key: `column.${name}`, fallback };
    return { name, label, placeholder: placeholder ?? label };
}

export type FilterFieldDateTuple = readonly [
    name: string,
    labelFallback: string,
    placeholderKey: string,
    placeholderFallback: string,
];

export function createFilterFieldDateFields(
    entries: ReadonlyArray<FilterFieldDateTuple>,
): FieldDescriptor[] {
    return createDateFilterFields(
        entries.map(
            ([name, labelFallback, placeholderKey, placeholderFallback]) => ({
                name,
                label: { key: `filter_field.${name}`, fallback: labelFallback },
                placeholder: {
                    key: placeholderKey,
                    fallback: placeholderFallback,
                },
            }),
        ),
    );
}

export function createFilterFieldStatusSelect(
    options: SelectOption[],
): FieldDescriptor {
    return createSelectFilterField(
        'status',
        { key: 'filter_field.status', fallback: 'Status' },
        options,
        { key: 'filter.select_status', fallback: 'Select status' },
    );
}

export function createApReportCommonFilterFields(
    searchPlaceholderKey: string,
    searchPlaceholderFallback: string,
): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: searchPlaceholderKey,
                fallback: searchPlaceholderFallback,
            },
        ),
        ...createAsyncLookupFilterFields([
            ['supplier_id', 'supplier', '/api/suppliers/select-options'],
            ['branch_id', 'branch', '/api/branches/select-options'],
        ]),
    ];
}

function TranslatedDatePicker({
    placeholder,
    ...rest
}: Readonly<
    Omit<React.ComponentProps<typeof FilterDatePicker>, 'placeholder'> & {
        placeholder: TranslatableLabel;
    }
>) {
    const translatedPlaceholder = useTranslatableText(placeholder);
    return <FilterDatePicker {...rest} placeholder={translatedPlaceholder} />;
}
TranslatedDatePicker.displayName = 'FilterDatePicker';

export function createDateFilterFields(
    configs: DateFilterConfig[],
): FieldDescriptor[] {
    return configs.map(({ name, label, placeholder }) => ({
        name,
        label,
        component: <TranslatedDatePicker placeholder={placeholder} />,
    }));
}

// Shared date-range fields used by report filters.
export function createDateRangeFilterFields(
    startPlaceholder: TranslatableLabel = {
        key: 'column.start_date',
        fallback: 'Start Date',
    },
    endPlaceholder: TranslatableLabel = {
        key: 'column.end_date',
        fallback: 'End Date',
    },
): FieldDescriptor[] {
    return [
        {
            name: 'start_date',
            label: { key: 'filter_field.start_date', fallback: 'Start Date' },
            component: <TranslatedDatePicker placeholder={startPlaceholder} />,
        },
        {
            name: 'end_date',
            label: { key: 'filter_field.end_date', fallback: 'End Date' },
            component: <TranslatedDatePicker placeholder={endPlaceholder} />,
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
            label: { key: 'filter_field.min_cost', fallback: 'Min Cost' },
            component: (
                <Input type="number" min={0} placeholder={minPlaceholder} />
            ),
        },
        {
            name: 'cost_max',
            label: { key: 'filter_field.max_cost', fallback: 'Max Cost' },
            component: (
                <Input type="number" min={0} placeholder={maxPlaceholder} />
            ),
        },
    ];
}
