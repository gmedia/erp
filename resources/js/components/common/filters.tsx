'use client';

import { AsyncSelect } from '@/components/common/AsyncSelect';
import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    LabelText,
    type TranslatableLabel,
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

function statusLabel(value: string, fallback: string): TranslatableLabel {
    return { key: `status.${value}`, fallback };
}

function filterLabel(key: string, fallback: string): TranslatableLabel {
    return { key: `filter.${key}`, fallback };
}

export const purchaseOrderStatusOptions: SelectOption[] = [
    { value: 'draft', label: statusLabel('draft', 'Draft') },
    {
        value: 'pending_approval',
        label: statusLabel('pending_approval', 'Pending Approval'),
    },
    { value: 'confirmed', label: statusLabel('confirmed', 'Confirmed') },
    { value: 'rejected', label: statusLabel('rejected', 'Rejected') },
    {
        value: 'partially_received',
        label: statusLabel('partially_received', 'Partially Received'),
    },
    {
        value: 'fully_received',
        label: statusLabel('fully_received', 'Fully Received'),
    },
    { value: 'cancelled', label: statusLabel('cancelled', 'Cancelled') },
    { value: 'closed', label: statusLabel('closed', 'Closed') },
];

export const currencyOptions: SelectOption[] = [
    { value: 'IDR', label: 'IDR' },
    { value: 'USD', label: 'USD' },
    { value: 'EUR', label: 'EUR' },
];

export const purchaseRequestPriorityOptions: SelectOption[] = [
    { value: 'low', label: statusLabel('low', 'Low') },
    { value: 'normal', label: statusLabel('normal', 'Normal') },
    { value: 'high', label: statusLabel('high', 'High') },
    { value: 'urgent', label: statusLabel('urgent', 'Urgent') },
];

export const purchaseRequestStatusOptions: SelectOption[] = [
    { value: 'draft', label: statusLabel('draft', 'Draft') },
    {
        value: 'pending_approval',
        label: statusLabel('pending_approval', 'Pending Approval'),
    },
    { value: 'approved', label: statusLabel('approved', 'Approved') },
    { value: 'rejected', label: statusLabel('rejected', 'Rejected') },
    {
        value: 'partially_ordered',
        label: statusLabel('partially_ordered', 'Partially Ordered'),
    },
    {
        value: 'fully_ordered',
        label: statusLabel('fully_ordered', 'Fully Ordered'),
    },
    { value: 'cancelled', label: statusLabel('cancelled', 'Cancelled') },
];

export const purchaseOrderStatusCategoryOptions: SelectOption[] = [
    { value: 'outstanding', label: statusLabel('outstanding', 'Outstanding') },
    {
        value: 'partially_received',
        label: statusLabel('partially_received', 'Partially Received'),
    },
    { value: 'closed', label: statusLabel('closed', 'Closed') },
];

export const fiscalYearStatusOptions: SelectOption[] = [
    { label: statusLabel('open', 'Open'), value: 'open' },
    { label: statusLabel('closed', 'Closed'), value: 'closed' },
    { label: statusLabel('locked', 'Locked'), value: 'locked' },
];

export const coaVersionStatusOptions: SelectOption[] = [
    { label: statusLabel('draft', 'Draft'), value: 'draft' },
    { label: statusLabel('active', 'Active'), value: 'active' },
    { label: statusLabel('archived', 'Archived'), value: 'archived' },
];

export const journalEntryStatusOptions: SelectOption[] = [
    { value: 'draft', label: statusLabel('draft', 'Draft') },
    { value: 'posted', label: statusLabel('posted', 'Posted') },
    { value: 'void', label: statusLabel('void', 'Void') },
];

export const approvalAuditEventOptions: SelectOption[] = [
    {
        value: 'submitted',
        label: filterLabel('approval_event_submitted', 'Submitted'),
    },
    {
        value: 'step_approved',
        label: filterLabel('approval_event_step_approved', 'Step Approved'),
    },
    {
        value: 'step_rejected',
        label: statusLabel('step_rejected', 'Step Rejected'),
    },
    {
        value: 'step_skipped',
        label: filterLabel('approval_event_step_skipped', 'Step Skipped'),
    },
    {
        value: 'auto_approved',
        label: statusLabel('auto_approved', 'Auto Approved'),
    },
    {
        value: 'escalated',
        label: filterLabel('approval_event_escalated', 'Escalated'),
    },
    {
        value: 'delegated',
        label: filterLabel('approval_event_delegated', 'Delegated'),
    },
    { value: 'cancelled', label: statusLabel('cancelled', 'Cancelled') },
    {
        value: 'resubmitted',
        label: filterLabel('approval_event_resubmitted', 'Resubmitted'),
    },
    { value: 'completed', label: statusLabel('completed', 'Completed') },
];

export const maintenanceTypeOptions: SelectOption[] = [
    {
        value: 'preventive',
        label: {
            key: 'form_fields.asset_maintenances.maintenance_type_preventive',
            fallback: 'Preventive',
        },
    },
    {
        value: 'corrective',
        label: {
            key: 'form_fields.asset_maintenances.maintenance_type_corrective',
            fallback: 'Corrective',
        },
    },
    {
        value: 'calibration',
        label: {
            key: 'form_fields.asset_maintenances.maintenance_type_calibration',
            fallback: 'Calibration',
        },
    },
    {
        value: 'other',
        label: {
            key: 'form_fields.asset_maintenances.maintenance_type_other',
            fallback: 'Other',
        },
    },
];

export const maintenanceStatusOptions: SelectOption[] = [
    { value: 'scheduled', label: statusLabel('scheduled', 'Scheduled') },
    { value: 'in_progress', label: statusLabel('in_progress', 'In Progress') },
    { value: 'completed', label: statusLabel('completed', 'Completed') },
    { value: 'cancelled', label: statusLabel('cancelled', 'Cancelled') },
];

export const assetStatusOptions: SelectOption[] = [
    { value: 'draft', label: statusLabel('draft', 'Draft') },
    { value: 'active', label: statusLabel('active', 'Active') },
    { value: 'maintenance', label: statusLabel('maintenance', 'Maintenance') },
    { value: 'disposed', label: statusLabel('disposed', 'Disposed') },
    { value: 'lost', label: statusLabel('lost', 'Lost') },
];

export const assetConditionOptions: SelectOption[] = [
    {
        value: 'good',
        label: { key: 'form_fields.assets.condition_good', fallback: 'Good' },
    },
    {
        value: 'needs_repair',
        label: {
            key: 'form_fields.assets.condition_needs_repair',
            fallback: 'Needs Repair',
        },
    },
    { value: 'damaged', label: statusLabel('damaged', 'Damaged') },
];

export const activeInactiveStatusOptions: SelectOption[] = [
    { value: 'active', label: statusLabel('active', 'Active') },
    { value: 'inactive', label: statusLabel('inactive', 'Inactive') },
];

export const employmentStatusOptions: SelectOption[] = [
    {
        value: 'regular',
        label: { key: 'status.regular', fallback: 'Regular' },
    },
    { value: 'intern', label: { key: 'status.intern', fallback: 'Intern' } },
];

export const productTypeOptions: SelectOption[] = [
    {
        value: 'raw_material',
        label: {
            key: 'form_fields.products.type_raw_material',
            fallback: 'Raw Material',
        },
    },
    {
        value: 'work_in_progress',
        label: {
            key: 'form_fields.products.type_work_in_progress',
            fallback: 'WIP',
        },
    },
    {
        value: 'finished_good',
        label: {
            key: 'form_fields.products.type_finished_good',
            fallback: 'Finished Good',
        },
    },
    {
        value: 'purchased_good',
        label: {
            key: 'form_fields.products.type_purchased_good',
            fallback: 'Purchased Good',
        },
    },
    {
        value: 'service',
        label: {
            key: 'form_fields.products.type_service',
            fallback: 'Service',
        },
    },
];

export const productStatusOptions: SelectOption[] = [
    { value: 'active', label: statusLabel('active', 'Active') },
    { value: 'inactive', label: statusLabel('inactive', 'Inactive') },
    {
        value: 'discontinued',
        label: statusLabel('discontinued', 'Discontinued'),
    },
];

export const inventoryStocktakeStatusOptions: SelectOption[] = [
    { value: 'draft', label: statusLabel('draft', 'Draft') },
    { value: 'in_progress', label: statusLabel('in_progress', 'In Progress') },
    { value: 'completed', label: statusLabel('completed', 'Completed') },
    { value: 'cancelled', label: statusLabel('cancelled', 'Cancelled') },
];

export const inventoryStocktakeVarianceResultOptions: SelectOption[] = [
    { value: 'surplus', label: statusLabel('surplus', 'Surplus') },
    { value: 'deficit', label: statusLabel('deficit', 'Deficit') },
];

export const draftConfirmedCancelledStatusOptions: SelectOption[] = [
    { value: 'draft', label: statusLabel('draft', 'Draft') },
    { value: 'confirmed', label: statusLabel('confirmed', 'Confirmed') },
    { value: 'cancelled', label: statusLabel('cancelled', 'Cancelled') },
];

export const approvalWorkflowStatusOptions: SelectOption[] = [
    { value: 'draft', label: statusLabel('draft', 'Draft') },
    {
        value: 'pending_approval',
        label: statusLabel('pending_approval', 'Pending Approval'),
    },
    { value: 'approved', label: statusLabel('approved', 'Approved') },
    { value: 'cancelled', label: statusLabel('cancelled', 'Cancelled') },
];

export const stockTransferStatusOptions: SelectOption[] = [
    { value: 'draft', label: statusLabel('draft', 'Draft') },
    {
        value: 'pending_approval',
        label: statusLabel('pending_approval', 'Pending Approval'),
    },
    { value: 'approved', label: statusLabel('approved', 'Approved') },
    { value: 'in_transit', label: statusLabel('in_transit', 'In Transit') },
    { value: 'received', label: statusLabel('received', 'Received') },
    { value: 'cancelled', label: statusLabel('cancelled', 'Cancelled') },
];

export const supplierReturnReasonOptions: SelectOption[] = [
    {
        value: 'defective',
        label: {
            key: 'form_fields.supplier_returns.reason_defective',
            fallback: 'Defective',
        },
    },
    {
        value: 'wrong_item',
        label: {
            key: 'form_fields.supplier_returns.reason_wrong_item',
            fallback: 'Wrong Item',
        },
    },
    {
        value: 'excess_quantity',
        label: {
            key: 'form_fields.supplier_returns.reason_excess_quantity',
            fallback: 'Excess Quantity',
        },
    },
    { value: 'damaged', label: statusLabel('damaged', 'Damaged') },
    {
        value: 'other',
        label: {
            key: 'form_fields.supplier_returns.reason_other',
            fallback: 'Other',
        },
    },
];

export const stockAdjustmentTypeOptions: SelectOption[] = [
    {
        value: 'damage',
        label: {
            key: 'form_fields.stock_adjustments.adjustment_type_damage',
            fallback: 'Damage',
        },
    },
    {
        value: 'expired',
        label: {
            key: 'form_fields.stock_adjustments.adjustment_type_expired',
            fallback: 'Expired',
        },
    },
    {
        value: 'shrinkage',
        label: {
            key: 'form_fields.stock_adjustments.adjustment_type_shrinkage',
            fallback: 'Shrinkage',
        },
    },
    {
        value: 'correction',
        label: {
            key: 'form_fields.stock_adjustments.adjustment_type_correction',
            fallback: 'Correction',
        },
    },
    {
        value: 'stocktake_result',
        label: {
            key: 'form_fields.stock_adjustments.adjustment_type_stocktake_result',
            fallback: 'Stocktake Result',
        },
    },
    {
        value: 'initial_stock',
        label: {
            key: 'form_fields.stock_adjustments.adjustment_type_initial_stock',
            fallback: 'Initial Stock',
        },
    },
    {
        value: 'other',
        label: {
            key: 'form_fields.stock_adjustments.adjustment_type_other',
            fallback: 'Other',
        },
    },
];

export const stockAdjustmentStatusOptions: SelectOption[] = [
    { value: 'draft', label: statusLabel('draft', 'Draft') },
    {
        value: 'pending_approval',
        label: statusLabel('pending_approval', 'Pending Approval'),
    },
    { value: 'approved', label: statusLabel('approved', 'Approved') },
    { value: 'cancelled', label: statusLabel('cancelled', 'Cancelled') },
];

export const stockMovementTypeOptions: SelectOption[] = [
    {
        value: 'goods_receipt',
        label: filterLabel('movement_goods_receipt', 'Goods Receipt'),
    },
    {
        value: 'supplier_return',
        label: filterLabel('movement_supplier_return', 'Supplier Return'),
    },
    {
        value: 'transfer_out',
        label: filterLabel('movement_transfer_out', 'Transfer Out'),
    },
    {
        value: 'transfer_in',
        label: filterLabel('movement_transfer_in', 'Transfer In'),
    },
    {
        value: 'adjustment_in',
        label: filterLabel('movement_adjustment_in', 'Adjustment In'),
    },
    {
        value: 'adjustment_out',
        label: filterLabel('movement_adjustment_out', 'Adjustment Out'),
    },
    {
        value: 'production_consume',
        label: filterLabel('movement_production_consume', 'Production Consume'),
    },
    {
        value: 'production_output',
        label: filterLabel('movement_production_output', 'Production Output'),
    },
    { value: 'sales', label: filterLabel('movement_sales', 'Sales') },
    {
        value: 'sales_return',
        label: filterLabel('movement_sales_return', 'Sales Return'),
    },
];

export const assetStocktakeVarianceResultOptions: SelectOption[] = [
    { value: 'damaged', label: statusLabel('damaged', 'Damaged') },
    { value: 'missing', label: statusLabel('missing', 'Missing') },
    { value: 'moved', label: statusLabel('moved', 'Moved') },
];

export type AsyncSelectFilterConfig = {
    name: string;
    label: TranslatableLabel;
    url: string;
    placeholder: string;
};

export type SelectFilterConfig = {
    name: string;
    label: TranslatableLabel;
    options: SelectOption[];
    placeholder: string;
};

export type DateFilterConfig = {
    name: string;
    label: TranslatableLabel;
    placeholder: string;
};

// Generic filter fields for simple entities
export function createSimpleEntityFilterFields(
    placeholder: TranslatableLabel,
): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            component: <TranslatedPlaceholderInput placeholder={placeholder} />,
        },
    ];
}

// Generic select filter field creator
export function createSelectFilterField(
    name: string,
    label: TranslatableLabel,
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

// Generic async select filter field creator
export function createAsyncSelectFilterField(
    name: string,
    label: TranslatableLabel,
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

export function createUnitFilterField(
    placeholder = 'Select unit',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'unit_id',
        'Unit',
        '/api/units',
        placeholder,
    );
}

export function createDepartmentFilterField(
    placeholder = 'Select a department',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'department_id',
        'Department',
        '/api/departments',
        placeholder,
    );
}

export function createPositionFilterField(
    placeholder = 'Select a position',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'position_id',
        'Position',
        '/api/positions',
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

export function createProductFilterField(
    placeholder = 'Select a product',
    label = 'Product',
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
    label: TranslatableLabel = 'Employee',
    placeholder = 'Select employee',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        name,
        label,
        '/api/employees',
        placeholder,
    );
}

export function createPurchaseOrderFilterField(
    placeholder = 'Select purchase order',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'purchase_order_id',
        'Purchase Order',
        '/api/purchase-orders',
        placeholder,
    );
}

export function createGoodsReceiptFilterField(
    placeholder = 'Select goods receipt',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'goods_receipt_id',
        'Goods Receipt',
        '/api/goods-receipts',
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

export function createCustomerCategoryFilterField(
    placeholder = 'Select Category',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'category_id',
        'Category',
        '/api/customer-categories',
        placeholder,
    );
}

export function createSupplierCategoryFilterField(
    placeholder = 'Select Category',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'category_id',
        'Category',
        '/api/supplier-categories',
        placeholder,
    );
}

export function createProductCategoryFilterField(
    name = 'product_category_id',
    label: TranslatableLabel = 'Category',
    placeholder = 'Select category',
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

export function createPurchasingReportScopeFilterFields(): FieldDescriptor[] {
    return [
        ...createSupplierWarehouseProductFilterFields(),
        ...createDateRangeFilterFields(),
    ];
}

export function createGoodsReceiptStatusFilterField(
    placeholder = 'All statuses',
): FieldDescriptor {
    return createSelectFilterField(
        'status',
        'Status',
        draftConfirmedCancelledStatusOptions,
        placeholder,
    );
}

export function createPurchaseOrderStatusFilterField(
    name = 'status',
    label = 'PO Status',
    placeholder = 'All statuses',
): FieldDescriptor {
    return createSelectFilterField(
        name,
        label,
        purchaseOrderStatusOptions,
        placeholder,
    );
}

export function createPurchaseOrderStatusCategoryFilterField(
    placeholder = 'All categories',
): FieldDescriptor {
    return createSelectFilterField(
        'status_category',
        'Status Category',
        purchaseOrderStatusCategoryOptions,
        placeholder,
    );
}

export function createBinaryStatusFilterField(
    name = 'status',
    label = 'Status',
    activeValue = 'active',
    inactiveValue = 'inactive',
    placeholder = 'All Statuses',
    activeLabel = 'Active',
    inactiveLabel = 'Inactive',
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
    placeholder = 'All Statuses',
): FieldDescriptor {
    return createSelectFilterField(
        'status',
        'Status',
        fiscalYearStatusOptions,
        placeholder,
    );
}

export function createCoaVersionStatusFilterField(
    placeholder = 'All Statuses',
): FieldDescriptor {
    return createSelectFilterField(
        'status',
        'Status',
        coaVersionStatusOptions,
        placeholder,
    );
}

export function createJournalEntryStatusFilterField(
    placeholder = 'Select status',
): FieldDescriptor {
    return createSelectFilterField(
        'status',
        'Status',
        journalEntryStatusOptions,
        placeholder,
    );
}

export function createApprovalAuditEventFilterField(
    placeholder = 'Select Event',
): FieldDescriptor {
    return createSelectFilterField(
        'event',
        'Event',
        approvalAuditEventOptions,
        placeholder,
    );
}

export function createPipelineFilterField(
    placeholder = 'Select a pipeline',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'pipeline_id',
        'Pipeline',
        '/api/pipelines',
        placeholder,
    );
}

export function createApprovalDelegationUserFilterFields(): FieldDescriptor[] {
    return [
        createUserFilterField(
            'delegator_user_id',
            'Delegator',
            'Select delegator',
        ),
        createUserFilterField(
            'delegate_user_id',
            'Delegate',
            'Select delegate',
        ),
    ];
}

export function createSupplierWarehouseFilterFields(
    supplierPlaceholder = 'Select a supplier',
    warehousePlaceholder = 'Select a warehouse',
): FieldDescriptor[] {
    return [
        createSupplierFilterField(supplierPlaceholder),
        createWarehouseFilterField(warehousePlaceholder),
    ];
}

export function createPurchaseRequestContextFilterFields(
    requesterPlaceholder = 'Select requester',
): FieldDescriptor[] {
    return [
        createBranchFilterField('Select a branch'),
        createDepartmentFilterField('Select a department'),
        createEmployeeLookupFilterField(
            'requested_by',
            'Requester',
            requesterPlaceholder,
        ),
    ];
}

export function createProductWarehouseFilterFields(
    productPlaceholder = 'Select a product',
    warehousePlaceholder = 'Select a warehouse',
): FieldDescriptor[] {
    return [
        createProductFilterField(productPlaceholder),
        createWarehouseFilterField(warehousePlaceholder),
    ];
}

export function createWarehouseStatusFilterFields(
    statusOptions: SelectOption[],
    warehousePlaceholder = 'Select warehouse',
    statusPlaceholder = 'Select status',
): FieldDescriptor[] {
    return [
        createWarehouseFilterField(warehousePlaceholder),
        createSelectFilterField(
            'status',
            'Status',
            statusOptions,
            statusPlaceholder,
        ),
    ];
}

export function createAssetCategoryBranchFilterFields(): FieldDescriptor[] {
    return [
        createAssetCategoryFilterField('Select a category'),
        createBranchFilterField('Select a branch'),
    ];
}

export function createAssetReportScopeFilterFields(
    searchPlaceholder: TranslatableLabel = 'Search code, name...',
): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', searchPlaceholder),
        ...createAssetCategoryBranchFilterFields(),
    ];
}

export function createAssetStocktakeVarianceScopeFilterFields(
    stocktakePlaceholder = 'Select a stocktake',
    resultPlaceholder = 'Select result',
): FieldDescriptor[] {
    return [
        createAssetStocktakeFilterField(stocktakePlaceholder),
        createBranchFilterField('Select a branch'),
        createSelectFilterField(
            'result',
            'Result',
            assetStocktakeVarianceResultOptions,
            resultPlaceholder,
        ),
    ];
}

export function createEmployeeOrganizationFilterFields(): FieldDescriptor[] {
    return [
        createDepartmentFilterField(),
        createPositionFilterField(),
        createBranchFilterField('Select a branch'),
    ];
}

export function createCustomerFilterSelectFields(): FieldDescriptor[] {
    return [
        createBranchFilterField('Select a branch'),
        createCustomerCategoryFilterField(),
        createSelectFilterField(
            'status',
            'Status',
            activeInactiveStatusOptions,
            'All statuses',
        ),
    ];
}

export function createSupplierFilterSelectFields(): FieldDescriptor[] {
    return [
        createBranchFilterField('Select a branch'),
        createSupplierCategoryFilterField(),
        createSelectFilterField(
            'status',
            'Status',
            activeInactiveStatusOptions,
            'Select Status',
        ),
    ];
}

export function createEmploymentStatusFilterField(
    placeholder = 'Select status',
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
        createBranchFilterField('Select branch'),
    ];
}

export function createProductTypeStatusFilterFields(): FieldDescriptor[] {
    return [
        createSelectFilterField(
            'type',
            'Type',
            productTypeOptions,
            'Select type',
        ),
        createSelectFilterField(
            'status',
            'Status',
            productStatusOptions,
            'Select status',
        ),
    ];
}

export function createInventoryStocktakeFilterSelectFields(): FieldDescriptor[] {
    return [
        createWarehouseFilterField('Select warehouse'),
        createProductCategoryFilterField(
            'product_category_id',
            { key: 'column.product_category', fallback: 'Product Category' },
            'Select category',
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            inventoryStocktakeStatusOptions,
            'Select status',
        ),
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
        createTextFilterField('search', 'Search', searchPlaceholder),
        createAsyncSelectFilterField(
            'customer_id',
            'Customer',
            '/api/customers',
            'Select Customer',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select Branch',
        ),
    ];
}

export function createInventoryStocktakeReportFilterField(
    placeholder = 'All stocktakes',
): FieldDescriptor {
    return createAsyncSelectFilterField(
        'inventory_stocktake_id',
        'Stocktake',
        '/api/inventory-stocktakes',
        placeholder,
    );
}

export function createInventoryStocktakeVarianceResultFilterField(
    placeholder = 'All results',
): FieldDescriptor {
    return createSelectFilterField(
        'result',
        'Result',
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
