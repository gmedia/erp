import * as z from 'zod';

// Schema for approval delegations
export const approvalDelegationFormSchema = z
    .object({
        delegator_user_id: z
            .string()
            .min(1, { message: 'Delegator is required.' }),
        delegate_user_id: z
            .string()
            .min(1, { message: 'Delegate is required.' }),
        approvable_type: z.string().optional(),
        start_date: z.date({ message: 'Start date is required.' }),
        end_date: z.date({ message: 'End date is required.' }),
        reason: z.string().optional(),
        is_active: z
            .union([z.boolean(), z.string()])
            .default(true)
            .transform((val) => val === true || val === 'true' || val === '1'),
    })
    .refine((data) => data.delegator_user_id !== data.delegate_user_id, {
        message: 'Delegator and Delegate cannot be the same user.',
        path: ['delegate_user_id'],
    })
    .refine((data) => data.end_date >= data.start_date, {
        message: 'End date must be after or equal to start date.',
        path: ['end_date'],
    });

// Schema for simple entities (departments, positions)
export const simpleEntitySchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
});

export type SimpleEntityFormData = z.infer<typeof simpleEntitySchema>;

/**
 * Product category form schema with name and description.
 */
export const productCategoryFormSchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    description: z.string().optional(),
});

export type ProductCategoryFormData = z.infer<typeof productCategoryFormSchema>;

/**
 * Unit form schema with name and symbol.
 */
export const unitFormSchema = z.object({
    name: z.string().min(1, { message: 'Name is required' }),
    symbol: z
        .string()
        .max(10, { message: 'Symbol cannot exceed 10 characters' })
        .optional()
        .nullable(),
});

export type UnitFormData = z.infer<typeof unitFormSchema>;

// Schema for employee form data
export const employeeFormSchema = z.object({
    employee_id: z.string().min(1, { message: 'Employee ID is required.' }),
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    email: z.string().email({ message: 'Please enter a valid email address.' }),
    phone: z
        .string()
        .min(10, { message: 'Phone number must be at least 10 digits.' })
        .regex(/^[\d\s\-+().]+$/, {
            message: 'Please enter a valid phone number.',
        })
        .or(z.literal(''))
        .optional(),
    department_id: z.string().min(1, { message: 'Department is required.' }),
    position_id: z.string().min(1, { message: 'Position is required.' }),
    branch_id: z.string().min(1, { message: 'Branch is required.' }),
    salary: z
        .string()
        .optional()
        .transform((val) => val?.replace(/[,\s]/g, '') || '')
        .pipe(
            z.union([
                z.literal(''),
                z.string().regex(/^\d+(\.\d{1,2})?$/, {
                    message:
                        'Please enter a valid salary amount (e.g., 50000 or 50000.00).',
                }),
            ]),
        ),
    hire_date: z.date({ message: 'Hire date is required.' }),
    employment_status: z.enum(['regular', 'intern'], {
        message: 'Employment status is required.',
    }),
    termination_date: z.date().optional().nullable(),
});

export type EmployeeFormData = z.infer<typeof employeeFormSchema>;

// Schema for customer form data
export const customerFormSchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    email: z.string().email({ message: 'Please enter a valid email address.' }),
    phone: z
        .string()
        .min(10, { message: 'Phone number must be at least 10 digits.' })
        .regex(/^[\d\s\-+().]+$/, {
            message: 'Please enter a valid phone number.',
        })
        .or(z.literal('')),
    address: z
        .string()
        .min(5, { message: 'Address must be at least 5 characters.' }),
    branch_id: z.string().min(1, { message: 'Branch is required.' }),
    category_id: z.string().min(1, { message: 'Category is required.' }),
    status: z.enum(['active', 'inactive'], {
        message: 'Status is required.',
    }),
    notes: z.string(),
});

export type CustomerFormData = z.infer<typeof customerFormSchema>;

// Schema for supplier form data
export const supplierFormSchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    email: z
        .string()
        .email({ message: 'Please enter a valid email address.' })
        .or(z.literal(''))
        .optional(),
    phone: z
        .string()
        .min(10, { message: 'Phone number must be at least 10 digits.' })
        .regex(/^[\d\s\-+().]+$/, {
            message: 'Please enter a valid phone number.',
        })
        .or(z.literal('')),
    address: z.string().optional(),
    branch_id: z.string().optional(),
    category_id: z.string().min(1, { message: 'Category is required.' }),
    status: z.enum(['active', 'inactive'], {
        message: 'Status is required.',
    }),
});

export type SupplierFormData = z.infer<typeof supplierFormSchema>;

// Schema for product form data
export const productFormSchema = z.object({
    code: z.string().min(2, { message: 'Code must be at least 2 characters.' }),
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    description: z.string().optional(),
    type: z.enum(
        [
            'raw_material',
            'work_in_progress',
            'finished_good',
            'purchased_good',
            'service',
        ],
        {
            message: 'Product type is required.',
        },
    ),
    category_id: z.string().min(1, { message: 'Category is required.' }),
    unit_id: z.string().min(1, { message: 'Unit is required.' }),
    branch_id: z.string().optional(),
    cost: z.string().min(1, { message: 'Cost is required.' }),
    selling_price: z.string().min(1, { message: 'Selling price is required.' }),
    markup_percentage: z.string().optional(),
    billing_model: z.enum(['one_time', 'subscription', 'both'], {
        message: 'Billing model is required.',
    }),
    is_recurring: z.boolean().default(false),
    trial_period_days: z.string().optional(),
    allow_one_time_purchase: z.boolean().default(true),
    is_manufactured: z.boolean().default(false),
    is_purchasable: z.boolean().default(true),
    is_sellable: z.boolean().default(true),
    is_taxable: z.boolean().default(true),
    status: z.enum(['active', 'inactive', 'discontinued'], {
        message: 'Status is required.',
    }),
    notes: z.string().optional(),
});

export type ProductFormData = z.infer<typeof productFormSchema>;

/**
 * Fiscal year form schema.
 */
export const fiscalYearFormSchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    start_date: z.date({ message: 'Start date is required.' }),
    end_date: z.date({ message: 'End date is required.' }),
    status: z.enum(['open', 'closed', 'locked'], {
        message: 'Status is required.',
    }),
});

export type FiscalYearFormData = z.infer<typeof fiscalYearFormSchema>;
export const coaVersionFormSchema = z.object({
    name: z.string().min(1, 'Name is required').max(255),
    fiscal_year_id: z
        .union([z.number(), z.string()])
        .transform((val) => Number(val))
        .pipe(z.number().min(1, 'Fiscal Year is required')),
    status: z.enum(['draft', 'active', 'archived']),
});

export type CoaVersionFormData = z.infer<typeof coaVersionFormSchema>;

// Schema for Journal Entry
export const journalEntryFormSchema = z.object({
    entry_date: z.date({ message: 'Date is required.' }),
    reference: z.string().optional(),
    description: z.string().min(1, { message: 'Description is required.' }),
    lines: z
        .array(
            z.object({
                account_id: z
                    .string()
                    .min(1, { message: 'Account is required.' }),
                debit: z.coerce.number().min(0),
                credit: z.coerce.number().min(0),
                memo: z.string().optional(),
            }),
        )
        .min(2, { message: 'At least 2 lines are required.' })
        .refine(
            (lines) => {
                const totalDebit = lines.reduce(
                    (sum, line) => sum + (line.debit || 0),
                    0,
                );
                const totalCredit = lines.reduce(
                    (sum, line) => sum + (line.credit || 0),
                    0,
                );
                return Math.abs(totalDebit - totalCredit) < 0.01;
            },
            {
                message: 'Total Debit and Total Credit must be equal.',
                path: ['root'],
            },
        ),
});

export type JournalEntryFormData = z.infer<typeof journalEntryFormSchema>;

/**
 * Asset category form schema with code, name, and useful life.
 */
export const assetCategoryFormSchema = z.object({
    code: z.string().min(2, { message: 'Code must be at least 2 characters.' }),
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    useful_life_months_default: z.coerce
        .number()
        .min(1, { message: 'Useful life must be at least 1 month.' }),
});

export type AssetCategoryFormData = z.infer<typeof assetCategoryFormSchema>;

/**
 * Asset model form schema.
 */
export const assetModelFormSchema = z.object({
    model_name: z
        .string()
        .min(2, { message: 'Model name must be at least 2 characters.' }),
    manufacturer: z.string().optional(),
    asset_category_id: z.string().min(1, { message: 'Category is required.' }),
    specs: z
        .string()
        .optional()
        .refine(
            (val) => {
                if (!val || val.trim() === '') return true;
                try {
                    JSON.parse(val);
                    return true;
                } catch {
                    return false;
                }
            },
            { message: 'Specs must be valid JSON.' },
        ),
});

export type AssetModelFormData = z.infer<typeof assetModelFormSchema>;

export const accountMappingFormSchema = z
    .object({
        source_coa_version_id: z
            .string()
            .min(1, { message: 'Source COA Version is required.' }),
        target_coa_version_id: z
            .string()
            .min(1, { message: 'Target COA Version is required.' }),
        source_account_id: z
            .string()
            .min(1, { message: 'Source account is required.' }),
        target_account_id: z
            .string()
            .min(1, { message: 'Target account is required.' }),
        type: z.enum(['merge', 'split', 'rename'], {
            message: 'Type is required.',
        }),
        notes: z.string().optional(),
    })
    .refine(
        (data) => data.source_coa_version_id !== data.target_coa_version_id,
        {
            message: 'Source and target COA versions must be different.',
            path: ['target_coa_version_id'],
        },
    );

export type AccountMappingFormData = z.infer<typeof accountMappingFormSchema>;

/**
 * Asset location form schema.
 */
export const assetLocationFormSchema = z.object({
    code: z.string().min(1, { message: 'Code is required.' }),
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    branch_id: z.string().min(1, { message: 'Branch is required.' }),
    parent_id: z.string().optional(),
});

export type AssetLocationFormData = z.infer<typeof assetLocationFormSchema>;

export const warehouseFormSchema = z.object({
    code: z.string().min(1, { message: 'Code is required.' }),
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    branch_id: z.string().min(1, { message: 'Branch is required.' }),
});

export type WarehouseFormData = z.infer<typeof warehouseFormSchema>;

/**
 * Asset form schema.
 */
export const assetFormSchema = z.object({
    asset_code: z
        .string()
        .min(2, { message: 'Asset code must be at least 2 characters.' }),
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    asset_category_id: z.string().min(1, { message: 'Category is required.' }),
    asset_model_id: z.string().default(''),
    serial_number: z.string().default(''),
    barcode: z.string().default(''),
    branch_id: z.string().min(1, { message: 'Branch is required.' }),
    asset_location_id: z.string().default(''),
    department_id: z.string().default(''),
    employee_id: z.string().default(''),
    supplier_id: z.string().default(''),
    purchase_date: z.date({ message: 'Purchase date is required.' }),
    purchase_cost: z.string().min(1, { message: 'Purchase cost is required.' }),
    currency: z.string().min(3).max(3).default('IDR'),
    warranty_end_date: z.date().optional().nullable(),
    status: z.enum(['draft', 'active', 'maintenance', 'disposed', 'lost'], {
        message: 'Status is required.',
    }),
    condition: z
        .enum(['good', 'needs_repair', 'damaged'])
        .optional()
        .nullable(),
    notes: z.string().default(''),
    depreciation_method: z
        .enum(['straight_line', 'declining_balance'])
        .default('straight_line'),
    depreciation_start_date: z.date().optional().nullable(),
    useful_life_months: z.string().default(''),
    salvage_value: z.string().default(''),
    depreciation_expense_account_id: z.string().default(''),
    accumulated_depr_account_id: z.string().default(''),
});

export type AssetFormData = z.infer<typeof assetFormSchema>;

/**
 * Asset movement form schema.
 */
export const assetMovementFormSchema = z.object({
    asset_id: z.string().min(1, { message: 'Asset is required.' }),
    movement_type: z.enum(
        ['transfer', 'assign', 'return', 'dispose', 'adjustment'],
        {
            message: 'Movement type is required.',
        },
    ),
    moved_at: z.date({ message: 'Movement date is required.' }),
    to_branch_id: z.string().optional(),
    to_location_id: z.string().optional(),
    to_department_id: z.string().optional(),
    to_employee_id: z.string().optional(),
    reference: z.string().optional(),
    notes: z.string().optional(),
});

export type AssetMovementFormData = z.infer<typeof assetMovementFormSchema>;

export const assetMaintenanceFormSchema = z.object({
    asset_id: z.string().min(1, { message: 'Asset is required.' }),
    maintenance_type: z.enum(
        ['preventive', 'corrective', 'calibration', 'other'],
        {
            message: 'Maintenance type is required.',
        },
    ),
    status: z.enum(['scheduled', 'in_progress', 'completed', 'cancelled'], {
        message: 'Status is required.',
    }),
    scheduled_at: z.date({ message: 'Scheduled date is required.' }),
    performed_at: z.date().optional().nullable(),
    supplier_id: z.string().default(''),
    cost: z.string().default('0'),
    notes: z.string().default(''),
});

export type AssetMaintenanceFormData = z.infer<
    typeof assetMaintenanceFormSchema
>;

export const assetStocktakeFormSchema = z.object({
    branch_id: z.string().min(1, { message: 'Branch is required.' }),
    reference: z.string().min(1, { message: 'Reference is required.' }),
    planned_at: z.date({ message: 'Planned date is required.' }),
    performed_at: z.date().optional().nullable(),
    status: z.enum(['draft', 'in_progress', 'completed', 'cancelled'], {
        message: 'Status is required.',
    }),
});

export type AssetStocktakeFormData = z.infer<typeof assetStocktakeFormSchema>;
 
export const assetDepreciationCalculationFormSchema = z.object({
    fiscal_year_id: z.string().min(1, { message: 'Fiscal year is required.' }),
    period_start: z.string().min(1, { message: 'Start period is required.' }),
    period_end: z.string().min(1, { message: 'End period is required.' }),
});
 
export type AssetDepreciationCalculationFormData = z.infer<
    typeof assetDepreciationCalculationFormSchema
>;

export const pipelineFormSchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    code: z.string().min(2, { message: 'Code must be at least 2 characters.' }),
    entity_type: z.string().min(1, { message: 'Entity Type is required.' }),
    description: z.string().optional(),
    version: z.string().optional(),
    is_active: z.union([z.boolean(), z.string()]).default(true),
    conditions: z
        .string()
        .optional()
        .refine(
            (val) => {
                if (!val || val.trim() === '') return true;
                try {
                    JSON.parse(val);
                    return true;
                } catch {
                    return false;
                }
            },
            { message: 'Conditions must be valid JSON.' },
        ),
});

export type PipelineFormData = z.infer<typeof pipelineFormSchema>;

export const pipelineStateFormSchema = z.object({
    code: z.string().min(1, 'Code is required'),
    name: z.string().min(1, 'Name is required'),
    type: z.enum(['initial', 'intermediate', 'final']),
    color: z.string().nullable().optional(),
    icon: z.string().nullable().optional(),
    description: z.string().nullable().optional(),
    sort_order: z
        .union([z.number(), z.string()])
        .transform((val) => Number(val)),
});

export type PipelineStateFormData = z.infer<typeof pipelineStateFormSchema>;

export const pipelineTransitionActionFormSchema = z.object({
    id: z.number().optional().nullable(),
    action_type: z.enum([
        'update_field',
        'create_record',
        'send_notification',
        'dispatch_job',
        'trigger_approval',
        'webhook',
        'custom',
    ]),
    execution_order: z
        .union([z.number(), z.string()])
        .transform((val) => Number(val)),
    config: z
        .string()
        .optional()
        .refine(
            (val) => {
                if (!val || val.trim() === '') return true;
                try {
                    JSON.parse(val);
                    return true;
                } catch {
                    return false;
                }
            },
            { message: 'Config must be valid JSON.' },
        ),
    is_async: z
        .union([z.boolean(), z.string()])
        .default(false)
        .transform((val) => val === 'true' || val === true),
    on_failure: z
        .enum(['abort', 'continue', 'log_and_continue'])
        .default('abort'),
    is_active: z
        .union([z.boolean(), z.string()])
        .default(true)
        .transform((val) => val === 'true' || val === true),
});

export type PipelineTransitionActionFormData = z.infer<
    typeof pipelineTransitionActionFormSchema
>;

export const pipelineTransitionFormSchema = z.object({
    from_state_id: z.number().or(z.string().transform(Number)),
    to_state_id: z.number().or(z.string().transform(Number)),
    name: z.string().min(1, 'Name is required'),
    code: z.string().min(1, 'Code is required'),
    description: z.string().nullable().optional(),
    required_permission: z.string().nullable().optional(),
    guard_conditions: z
        .string()
        .optional()
        .refine(
            (val) => {
                if (!val || val.trim() === '') return true;
                try {
                    JSON.parse(val);
                    return true;
                } catch {
                    return false;
                }
            },
            { message: 'Guard conditions must be valid JSON.' },
        ),
    requires_confirmation: z
        .union([z.boolean(), z.string()])
        .default(false)
        .transform((val) => val === 'true' || val === true),
    requires_comment: z
        .union([z.boolean(), z.string()])
        .default(false)
        .transform((val) => val === 'true' || val === true),
    requires_approval: z
        .union([z.boolean(), z.string()])
        .default(false)
        .transform((val) => val === 'true' || val === true),
    sort_order: z
        .union([z.number(), z.string()])
        .transform((val) => Number(val)),
    is_active: z
        .union([z.boolean(), z.string()])
        .default(true)
        .transform((val) => val === 'true' || val === true),
    actions: z.array(pipelineTransitionActionFormSchema).optional(),
});

export type PipelineTransitionFormData = z.infer<
    typeof pipelineTransitionFormSchema
>;

export const approvalFlowFormSchema = z.object({
    name: z.string().min(1, 'Name is required'),
    code: z.string().min(1, 'Code is required'),
    approvable_type: z.string().min(1, 'Approvable Type is required'),
    description: z.string().nullable().optional(),
    is_active: z
        .union([z.boolean(), z.string()])
        .transform((val) => val === true || val === 'true'),
    conditions: z.string().nullable().optional(),
    steps: z
        .array(
            z.object({
                id: z.number().optional(),
                name: z.string().min(1, 'Step name is required'),
                approver_type: z.literal('user'),
                approver_user_id: z.preprocess(
                    (val) => (val === '' || val === null ? null : Number(val)),
                    z.number().nullable().refine((value) => value !== null, {
                        message: 'Approver user is required',
                    }),
                ),
                required_action: z.enum(['approve', 'review', 'acknowledge']),
                auto_approve_after_hours: z.preprocess(
                    (val) => (val === '' || val === null ? null : Number(val)),
                    z.number().nullable().optional(),
                ),
                escalate_after_hours: z.preprocess(
                    (val) => (val === '' || val === null ? null : Number(val)),
                    z.number().nullable().optional(),
                ),
                escalation_user_id: z.preprocess(
                    (val) => (val === '' || val === null ? null : Number(val)),
                    z.number().nullable().optional(),
                ),
                can_reject: z
                    .union([z.boolean(), z.string()])
                    .transform((val) => val === true || val === 'true'),
            }),
        )
            .min(1, 'At least one approval step is required'),
});

export type ApprovalFlowFormData = z.infer<typeof approvalFlowFormSchema>;

export const stockTransferFormSchema = z.object({
    transfer_number: z.string().optional(),
    from_warehouse_id: z
        .string()
        .min(1, { message: 'From warehouse is required.' }),
    to_warehouse_id: z
        .string()
        .min(1, { message: 'To warehouse is required.' }),
    transfer_date: z.date({ message: 'Transfer date is required.' }),
    expected_arrival_date: z.date().optional().nullable(),
    status: z.enum(
        [
            'draft',
            'pending_approval',
            'approved',
            'in_transit',
            'received',
            'cancelled',
        ],
        {
            message: 'Status is required.',
        },
    ),
    notes: z.string().optional(),
    requested_by: z.string().optional(),
    items: z
        .array(
            z.object({
                product_id: z
                    .string()
                    .min(1, { message: 'Product is required.' }),
                product_label: z.string().optional(),
                unit_id: z.string().min(1, { message: 'Unit is required.' }),
                unit_label: z.string().optional(),
                quantity: z.coerce
                    .number()
                    .gt(0, { message: 'Quantity must be greater than 0.' }),
                quantity_received: z.coerce
                    .number()
                    .min(0)
                    .optional()
                    .default(0),
                unit_cost: z.coerce.number().min(0).optional().default(0),
                notes: z.string().optional(),
            }),
        )
        .min(1, { message: 'At least 1 item is required.' }),
});

export type StockTransferFormData = z.infer<typeof stockTransferFormSchema>;

export const inventoryStocktakeFormSchema = z.object({
    stocktake_number: z.string().optional(),
    warehouse_id: z.string().min(1, { message: 'Warehouse is required.' }),
    stocktake_date: z.date({ message: 'Stocktake date is required.' }),
    status: z.enum(['draft', 'in_progress', 'completed', 'cancelled'], {
        message: 'Status is required.',
    }),
    product_category_id: z.string().optional(),
    notes: z.string().optional(),
    items: z
        .array(
            z.object({
                product_id: z
                    .string()
                    .min(1, { message: 'Product is required.' }),
                unit_id: z.string().min(1, { message: 'Unit is required.' }),
                system_quantity: z.coerce
                    .number()
                    .min(0, { message: 'System quantity must be at least 0.' }),
                counted_quantity: z.coerce
                    .number()
                    .min(0, { message: 'Counted quantity must be at least 0.' })
                    .optional()
                    .default(0),
                notes: z.string().optional(),
            }),
        )
        .min(1, { message: 'At least 1 item is required.' }),
});

export type InventoryStocktakeFormData = z.infer<
    typeof inventoryStocktakeFormSchema
>;

export const stockAdjustmentFormSchema = z.object({
    adjustment_number: z.string().optional(),
    warehouse_id: z.string().min(1, { message: 'Warehouse is required.' }),
    adjustment_date: z.date({ message: 'Adjustment date is required.' }),
    adjustment_type: z.enum(
        [
            'damage',
            'expired',
            'shrinkage',
            'correction',
            'stocktake_result',
            'initial_stock',
            'other',
        ],
        {
            message: 'Adjustment type is required.',
        },
    ),
    status: z.enum(['draft', 'pending_approval', 'approved', 'cancelled'], {
        message: 'Status is required.',
    }),
    inventory_stocktake_id: z.string().optional(),
    notes: z.string().optional(),
    items: z
        .array(
            z.object({
                product_id: z
                    .string()
                    .min(1, { message: 'Product is required.' }),
                unit_id: z.string().min(1, { message: 'Unit is required.' }),
                quantity_before: z.coerce
                    .number()
                    .min(0, { message: 'Quantity before must be at least 0.' })
                    .optional()
                    .default(0),
                quantity_adjusted: z.coerce
                    .number()
                    .refine((n) => n !== 0, {
                        message: 'Quantity adjusted cannot be 0.',
                    }),
                unit_cost: z.coerce
                    .number()
                    .min(0, { message: 'Unit cost must be at least 0.' })
                    .optional()
                    .default(0),
                reason: z.string().optional(),
            }),
        )
        .min(1, { message: 'At least 1 item is required.' }),
});

export type StockAdjustmentFormData = z.infer<typeof stockAdjustmentFormSchema>;

export const purchaseRequestFormSchema = z.object({
    pr_number: z.string().optional(),
    branch_id: z.string().min(1, { message: 'Branch is required.' }),
    department_id: z.string().optional(),
    requested_by: z.string().optional(),
    request_date: z.date({ message: 'Request date is required.' }),
    required_date: z.date().optional().nullable(),
    priority: z.enum(['low', 'normal', 'high', 'urgent'], {
        message: 'Priority is required.',
    }),
    status: z.enum(['draft', 'pending_approval', 'approved', 'rejected', 'partially_ordered', 'fully_ordered', 'cancelled'], {
        message: 'Status is required.',
    }),
    estimated_amount: z.coerce.number().min(0).optional(),
    notes: z.string().optional(),
    rejection_reason: z.string().optional(),
    items: z.array(z.object({
        product_id: z.string().min(1, { message: 'Product is required.' }),
        unit_id: z.string().min(1, { message: 'Unit is required.' }),
        quantity: z.coerce.number().gt(0, { message: 'Quantity must be greater than 0.' }),
        estimated_unit_price: z.coerce.number().min(0).optional().default(0),
        notes: z.string().optional(),
    })).min(1, { message: 'At least 1 item is required.' }),
});

export type PurchaseRequestFormData = z.infer<typeof purchaseRequestFormSchema>;

export const purchaseOrderFormSchema = z.object({
    po_number: z.string().optional(),
    supplier_id: z.string().min(1, { message: 'Supplier is required.' }),
    warehouse_id: z.string().min(1, { message: 'Warehouse is required.' }),
    order_date: z.date({ message: 'Order date is required.' }),
    expected_delivery_date: z.date().optional().nullable(),
    payment_terms: z.string().optional(),
    currency: z.string().min(3, { message: 'Currency is required.' }).max(3),
    status: z.enum(['draft', 'pending_approval', 'confirmed', 'rejected', 'partially_received', 'fully_received', 'cancelled', 'closed'], {
        message: 'Status is required.',
    }),
    notes: z.string().optional(),
    shipping_address: z.string().optional(),
    items: z.array(z.object({
        purchase_request_item_id: z.string().optional(),
        product_id: z.string().min(1, { message: 'Product is required.' }),
        unit_id: z.string().min(1, { message: 'Unit is required.' }),
        quantity: z.coerce.number().gt(0, { message: 'Quantity must be greater than 0.' }),
        unit_price: z.coerce.number().min(0, { message: 'Unit price must be at least 0.' }),
        discount_percent: z.coerce.number().min(0).max(100).optional().default(0),
        tax_percent: z.coerce.number().min(0).max(100).optional().default(0),
        notes: z.string().optional(),
    })).min(1, { message: 'At least 1 item is required.' }),
});

export type PurchaseOrderFormData = z.infer<typeof purchaseOrderFormSchema>;

export const goodsReceiptFormSchema = z.object({
    gr_number: z.string().optional(),
    purchase_order_id: z.string().min(1, { message: 'Purchase order is required.' }),
    warehouse_id: z.string().min(1, { message: 'Warehouse is required.' }),
    receipt_date: z.date({ message: 'Receipt date is required.' }),
    supplier_delivery_note: z.string().optional(),
    status: z.enum(['draft', 'confirmed', 'cancelled'], {
        message: 'Status is required.',
    }),
    received_by: z.string().optional(),
    notes: z.string().optional(),
    items: z.array(z.object({
        purchase_order_item_id: z.string().min(1, { message: 'PO Item is required.' }),
        product_id: z.string().min(1, { message: 'Product is required.' }),
        unit_id: z.string().min(1, { message: 'Unit is required.' }),
        quantity_received: z.coerce.number().gt(0, { message: 'Quantity received must be greater than 0.' }),
        quantity_accepted: z.coerce.number().min(0, { message: 'Quantity accepted must be at least 0.' }),
        quantity_rejected: z.coerce.number().min(0).optional().default(0),
        unit_price: z.coerce.number().min(0, { message: 'Unit price must be at least 0.' }),
        notes: z.string().optional(),
    })).min(1, { message: 'At least 1 item is required.' }),
});

export type GoodsReceiptFormData = z.infer<typeof goodsReceiptFormSchema>;

export const supplierReturnFormSchema = z.object({
    return_number: z.string().optional(),
    purchase_order_id: z.string().min(1, { message: 'Purchase order is required.' }),
    goods_receipt_id: z.string().optional(),
    supplier_id: z.string().min(1, { message: 'Supplier is required.' }),
    warehouse_id: z.string().min(1, { message: 'Warehouse is required.' }),
    return_date: z.date({ message: 'Return date is required.' }),
    reason: z.enum(['defective', 'wrong_item', 'excess_quantity', 'damaged', 'other'], {
        message: 'Reason is required.',
    }),
    status: z.enum(['draft', 'confirmed', 'cancelled'], {
        message: 'Status is required.',
    }),
    notes: z.string().optional(),
    items: z.array(z.object({
        goods_receipt_item_id: z.string().min(1, { message: 'GR item is required.' }),
        product_id: z.string().min(1, { message: 'Product is required.' }),
        unit_id: z.string().optional(),
        quantity_returned: z.coerce.number().gt(0, { message: 'Quantity returned must be greater than 0.' }),
        unit_price: z.coerce.number().min(0, { message: 'Unit price must be at least 0.' }),
        notes: z.string().optional(),
    })).min(1, { message: 'At least 1 item is required.' }),
});

export type SupplierReturnFormData = z.infer<typeof supplierReturnFormSchema>;
