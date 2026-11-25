import * as z from 'zod';

/** Percentage 0-100, optional, defaults to 0 (shared by line items). */
const percentField = () =>
    z.coerce.number().min(0).max(100).optional().default(0);

const requiredMessage = (label: string) => ({
    message: `${label} is required.`,
});

const atLeastOneItemMessage = requiredMessage('At least 1 item');

const linePricingFields = (messages: {
    quantity: string;
    unitPrice: string;
}) => ({
    quantity: z.coerce.number().gt(0, { message: messages.quantity }),
    unit_price: z.coerce.number().min(0, { message: messages.unitPrice }),
    discount_percent: percentField(),
    tax_percent: percentField(),
    notes: z.string().optional(),
});

const isValidEmailAddress = (value: string): boolean => {
    if (!value || value.includes(' ')) {
        return false;
    }

    const atIndex = value.indexOf('@');
    if (atIndex <= 0 || atIndex !== value.lastIndexOf('@')) {
        return false;
    }

    const localPart = value.slice(0, atIndex);
    const domainPart = value.slice(atIndex + 1);

    if (!localPart || !domainPart) {
        return false;
    }

    const dotIndex = domainPart.lastIndexOf('.');
    if (dotIndex <= 0 || dotIndex === domainPart.length - 1) {
        return false;
    }

    return true;
};

const emailValidator = () =>
    z.string().refine((value) => isValidEmailAddress(value), {
        message: 'Please enter a valid email address.',
    });

// Schema for approval delegations
export const approvalDelegationFormSchema = z
    .object({
        delegator_user_id: z.string().min(1, {
            message: 'validation.approval_delegation.delegator_required',
        }),
        delegate_user_id: z.string().min(1, {
            message: 'validation.approval_delegation.delegate_required',
        }),
        approvable_type: z.string().optional(),
        start_date: z.string().min(1, {
            message: 'validation.approval_delegation.start_date_required',
        }),
        end_date: z.string().min(1, {
            message: 'validation.approval_delegation.end_date_required',
        }),
        reason: z.string().optional(),
        is_active: z
            .union([z.boolean(), z.string()])
            .default(true)
            .transform((val) => val === true || val === 'true' || val === '1'),
    })
    .refine((data) => data.delegator_user_id !== data.delegate_user_id, {
        message: 'validation.approval_delegation.same_user',
        path: ['delegate_user_id'],
    })
    .refine((data) => data.end_date >= data.start_date, {
        message: 'validation.approval_delegation.end_before_start',
        path: ['end_date'],
    });

// Schema for simple entities (departments, positions)
export const simpleEntitySchema = z.object({
    name: z.string().min(2, {
        message: 'validation.simple_entity.name_min_2',
    }),
});

export type SimpleEntityFormData = z.infer<typeof simpleEntitySchema>;

/**
 * Product category form schema with name and description.
 */
export const productCategoryFormSchema = z.object({
    name: z
        .string()
        .min(2, { message: 'validation.product_category.name_min_2' }),
    description: z.string().optional(),
});

export type ProductCategoryFormData = z.infer<typeof productCategoryFormSchema>;

/**
 * Unit form schema with name and symbol.
 */
export const unitFormSchema = z.object({
    name: z.string().min(1, { message: 'validation.unit.name_required' }),
    symbol: z
        .string()
        .max(10, { message: 'validation.unit.symbol_max_10' })
        .optional()
        .nullable(),
});

export type UnitFormData = z.infer<typeof unitFormSchema>;

// Schema for employee form data
export const employeeFormSchema = z.object({
    employee_id: z
        .string()
        .min(1, { message: 'validation.employee.employee_id_required' }),
    name: z.string().min(2, { message: 'validation.employee.name_min_2' }),
    email: emailValidator(),
    phone: z
        .string()
        .min(10, { message: 'Phone number must be at least 10 digits.' })
        .regex(/^[\d\s\-+().]+$/, {
            message: 'Please enter a valid phone number.',
        })
        .or(z.literal(''))
        .optional(),
    company_id: z
        .string()
        .min(1, { message: 'validation.employee.company_required' }),
    department_id: z
        .string()
        .min(1, { message: 'validation.employee.department_required' }),
    position_id: z
        .string()
        .min(1, { message: 'validation.employee.position_required' }),
    branch_id: z
        .string()
        .min(1, { message: 'validation.employee.branch_required' }),
    salary: z
        .string()
        .optional()
        .transform((val) => val?.replaceAll(',', '').replaceAll(' ', '') || '')
        .pipe(
            z.union([
                z.literal(''),
                z.string().regex(/^\d+(\.\d{1,2})?$/, {
                    message:
                        'Please enter a valid salary amount (e.g., 50000 or 50000.00).',
                }),
            ]),
        ),
    hire_date: z
        .string()
        .min(1, { message: 'validation.employee.hire_date_required' }),
    employment_status: z.enum(['regular', 'intern'], {
        message: 'validation.employee.employment_status_required',
    }),
    termination_date: z.string().optional().nullable(),
});

export type EmployeeFormData = z.infer<typeof employeeFormSchema>;

// Schema for customer form data
export const customerFormSchema = z.object({
    name: z.string().min(2, { message: 'validation.customer.name_min_2' }),
    email: emailValidator(),
    phone: z
        .string()
        .min(10, { message: 'Phone number must be at least 10 digits.' })
        .regex(/^[\d\s\-+().]+$/, {
            message: 'Please enter a valid phone number.',
        })
        .or(z.literal('')),
    address: z
        .string()
        .min(5, { message: 'validation.customer.address_min_5' }),
    branch_id: z
        .string()
        .min(1, { message: 'validation.customer.branch_required' }),
    category_id: z
        .string()
        .min(1, { message: 'validation.customer.category_required' }),
    status: z.enum(['active', 'inactive'], {
        message: 'validation.customer.status_required',
    }),
    notes: z.string(),
});

export type CustomerFormData = z.infer<typeof customerFormSchema>;

// Schema for supplier form data
export const supplierFormSchema = z.object({
    name: z.string().min(2, { message: 'validation.supplier.name_min_2' }),
    email: z.union([emailValidator(), z.literal('')]).optional(),
    phone: z
        .string()
        .min(10, { message: 'Phone number must be at least 10 digits.' })
        .regex(/^[\d\s\-+().]+$/, {
            message: 'Please enter a valid phone number.',
        })
        .or(z.literal('')),
    address: z.string().optional(),
    branch_id: z.string().optional(),
    category_id: z
        .string()
        .min(1, { message: 'validation.supplier.category_required' }),
    status: z.enum(['active', 'inactive'], {
        message: 'validation.supplier.status_required',
    }),
});

export type SupplierFormData = z.infer<typeof supplierFormSchema>;

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
    product_category_id: z
        .string()
        .min(1, { message: 'Category is required.' }),
    unit_id: z.string().min(1, requiredMessage('Unit')),
    branch_id: z.string().optional(),
    cost: z.string().min(1, { message: 'Cost is required.' }),
    selling_price: z.string().min(1, { message: 'Selling price is required.' }),
    billing_model: z.enum(['one_time', 'subscription', 'both'], {
        message: 'Billing model is required.',
    }),
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
    name: z.string().min(2, {
        message: 'validation.fiscal_year.name_min_2',
    }),
    start_date: z.string().min(1, {
        message: 'validation.fiscal_year.start_date_required',
    }),
    end_date: z.string().min(1, {
        message: 'validation.fiscal_year.end_date_required',
    }),
    status: z.enum(['open', 'closed', 'locked'], {
        message: 'validation.fiscal_year.status_required',
    }),
});

export type FiscalYearFormData = z.infer<typeof fiscalYearFormSchema>;
export const coaVersionFormSchema = z.object({
    name: z.string().min(1, 'validation.coa_version.name_required').max(255),
    fiscal_year_id: z
        .union([z.number(), z.string()])
        .transform(Number)
        .pipe(z.number().min(1, 'validation.coa_version.fiscal_year_required')),
    status: z.enum(['draft', 'active', 'archived']),
});

export type CoaVersionFormData = z.infer<typeof coaVersionFormSchema>;

// Schema for Journal Entry
export const journalEntryFormSchema = z.object({
    entry_date: z.string().min(1, {
        message: 'validation.journal_entry.date_required',
    }),
    reference: z.string().optional(),
    description: z.string().min(1, {
        message: 'validation.journal_entry.description_required',
    }),
    lines: z
        .array(
            z.object({
                account_id: z.string().min(1, {
                    message: 'validation.journal_entry.account_required',
                }),
                debit: z.coerce.number().min(0),
                credit: z.coerce.number().min(0),
                memo: z.string().optional(),
                account_name: z.string().optional(),
                account_code: z.string().optional(),
                branch_id: z.string().optional(),
                branch_name: z.string().optional(),
            }),
        )
        .min(2, { message: 'validation.journal_entry.lines_min_two' })
        .refine(
            (lines) =>
                lines.every((line) => {
                    const hasDebit = line.debit > 0;
                    const hasCredit = line.credit > 0;

                    return hasDebit !== hasCredit;
                }),
            {
                message: 'validation.journal_entry.line_debit_or_credit',
                path: ['root'],
            },
        )
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
                message: 'validation.journal_entry.totals_must_balance',
                path: ['root'],
            },
        ),
});

export type JournalEntryFormData = z.infer<typeof journalEntryFormSchema>;

/**
 * Asset category form schema with code, name, and useful life.
 */
export const assetCategoryFormSchema = z.object({
    code: z.string().min(2, {
        message: 'validation.asset_category.code_min_2',
    }),
    name: z.string().min(2, {
        message: 'validation.asset_category.name_min_2',
    }),
    useful_life_months_default: z.coerce.number().min(1, {
        message: 'validation.asset_category.useful_life_min_1',
    }),
});

export type AssetCategoryFormData = z.infer<typeof assetCategoryFormSchema>;

/**
 * Asset model form schema.
 */
export const assetModelFormSchema = z.object({
    model_name: z.string().min(2, {
        message: 'validation.asset_model.model_name_min_2',
    }),
    manufacturer: z.string().optional(),
    asset_category_id: z.string().min(1, {
        message: 'validation.asset_model.category_required',
    }),
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
            { message: 'validation.asset_model.specs_json' },
        ),
});

export type AssetModelFormData = z.infer<typeof assetModelFormSchema>;

export const accountMappingFormSchema = z
    .object({
        source_coa_version_id: z.string().min(1, {
            message: 'validation.account_mapping.source_coa_version_required',
        }),
        target_coa_version_id: z.string().min(1, {
            message: 'validation.account_mapping.target_coa_version_required',
        }),
        source_account_id: z.string().min(1, {
            message: 'validation.account_mapping.source_account_required',
        }),
        target_account_id: z.string().min(1, {
            message: 'validation.account_mapping.target_account_required',
        }),
        type: z.enum(['merge', 'split', 'rename'], {
            message: 'validation.account_mapping.type_required',
        }),
        notes: z.string().optional(),
    })
    .refine(
        (data) => data.source_coa_version_id !== data.target_coa_version_id,
        {
            message: 'validation.account_mapping.versions_must_differ',
            path: ['target_coa_version_id'],
        },
    );

export type AccountMappingFormData = z.infer<typeof accountMappingFormSchema>;

/**
 * Asset location form schema.
 */
export const assetLocationFormSchema = z.object({
    code: z.string().min(1, {
        message: 'validation.asset_location.code_required',
    }),
    name: z.string().min(2, {
        message: 'validation.asset_location.name_min_2',
    }),
    branch_id: z.string().min(1, {
        message: 'validation.asset_location.branch_required',
    }),
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
    asset_code: z.string().min(2, {
        message: 'validation.asset.asset_code_min_2',
    }),
    name: z.string().min(2, { message: 'validation.asset.name_min_2' }),
    asset_category_id: z.string().min(1, {
        message: 'validation.asset.category_required',
    }),
    asset_model_id: z.string().default(''),
    serial_number: z.string().default(''),
    barcode: z.string().default(''),
    branch_id: z.string().min(1, {
        message: 'validation.asset.branch_required',
    }),
    asset_location_id: z.string().default(''),
    department_id: z.string().default(''),
    employee_id: z.string().default(''),
    supplier_id: z.string().default(''),
    purchase_date: z.string().min(1, {
        message: 'validation.asset.purchase_date_required',
    }),
    purchase_cost: z.string().min(1, {
        message: 'validation.asset.purchase_cost_required',
    }),
    currency: z.string().min(3).max(3).default('IDR'),
    warranty_end_date: z.string().optional().nullable(),
    status: z.enum(['draft', 'active', 'maintenance', 'disposed', 'lost'], {
        message: 'validation.asset.status_required',
    }),
    condition: z
        .enum(['good', 'needs_repair', 'damaged'])
        .optional()
        .nullable(),
    notes: z.string().default(''),
    depreciation_method: z
        .enum(['straight_line', 'declining_balance'])
        .default('straight_line'),
    depreciation_start_date: z.string().optional().nullable(),
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
    asset_id: z.string().min(1, {
        message: 'validation.asset_movement.asset_required',
    }),
    movement_type: z.enum(
        ['transfer', 'assign', 'return', 'dispose', 'adjustment'],
        {
            message: 'validation.asset_movement.movement_type_required',
        },
    ),
    moved_at: z.string().min(1, {
        message: 'validation.asset_movement.moved_at_required',
    }),
    to_branch_id: z.string().optional(),
    to_location_id: z.string().optional(),
    to_department_id: z.string().optional(),
    to_employee_id: z.string().optional(),
    reference: z.string().optional(),
    notes: z.string().optional(),
    sale_amount: z.string().optional(),
    sale_account_id: z.string().optional(),
});

export type AssetMovementFormData = z.infer<typeof assetMovementFormSchema>;

export const assetMaintenanceFormSchema = z.object({
    asset_id: z.string().min(1, {
        message: 'validation.asset_maintenance.asset_required',
    }),
    maintenance_type: z.enum(
        ['preventive', 'corrective', 'calibration', 'other'],
        {
            message: 'validation.asset_maintenance.maintenance_type_required',
        },
    ),
    status: z.enum(['scheduled', 'in_progress', 'completed', 'cancelled'], {
        message: 'validation.asset_maintenance.status_required',
    }),
    scheduled_at: z.string().min(1, {
        message: 'validation.asset_maintenance.scheduled_at_required',
    }),
    performed_at: z.string().optional().nullable(),
    supplier_id: z.string().default(''),
    cost: z.string().default('0'),
    notes: z.string().default(''),
});

export type AssetMaintenanceFormData = z.infer<
    typeof assetMaintenanceFormSchema
>;

export const assetStocktakeFormSchema = z.object({
    branch_id: z.string().min(1, {
        message: 'validation.asset_stocktake.branch_required',
    }),
    reference: z.string().min(1, {
        message: 'validation.asset_stocktake.reference_required',
    }),
    planned_at: z.string().min(1, {
        message: 'validation.asset_stocktake.planned_at_required',
    }),
    performed_at: z.string().optional().nullable(),
    status: z.enum(['draft', 'in_progress', 'completed', 'cancelled'], {
        message: 'validation.asset_stocktake.status_required',
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
    name: z.string().min(2, { message: 'validation.pipeline.name_min_2' }),
    code: z.string().min(2, { message: 'validation.pipeline.code_min_2' }),
    entity_type: z.string().min(1, {
        message: 'validation.pipeline.entity_type_required',
    }),
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
            { message: 'validation.pipeline.conditions_json' },
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
    sort_order: z.union([z.number(), z.string()]).transform(Number),
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
    execution_order: z.union([z.number(), z.string()]).transform(Number),
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
    sort_order: z.union([z.number(), z.string()]).transform(Number),
    is_active: z
        .union([z.boolean(), z.string()])
        .default(true)
        .transform((val) => val === 'true' || val === true),
    actions: z.array(pipelineTransitionActionFormSchema).optional(),
});

export type PipelineTransitionFormData = z.infer<
    typeof pipelineTransitionFormSchema
>;

export const approvalFlowStepSchema = z.object({
    id: z.number().optional(),
    name: z.string().min(1, 'validation.approval_flow.step_name_required'),
    approver_type: z.literal('user'),
    approver_user_id: z.preprocess(
        (val) => (val === '' || val === null ? null : Number(val)),
        z
            .number()
            .nullable()
            .refine((value) => value !== null, {
                message: 'validation.approval_flow.approver_user_required',
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
        .default(true)
        .transform((val) => val === true || val === 'true'),
});

export const approvalFlowFormSchema = z.object({
    name: z.string().min(1, 'validation.approval_flow.name_required'),
    code: z.string().min(1, 'validation.approval_flow.code_required'),
    approvable_type: z
        .string()
        .min(1, 'validation.approval_flow.approvable_type_required'),
    description: z.string().nullable().optional(),
    is_active: z
        .union([z.boolean(), z.string()])
        .transform((val) => val === true || val === 'true'),
    conditions: z.string().nullable().optional(),
    steps: z
        .array(approvalFlowStepSchema)
        .min(1, 'validation.approval_flow.steps_min_one'),
});

export type ApprovalFlowFormData = z.infer<typeof approvalFlowFormSchema>;

export const stockTransferQuantityReceivedSchema = z.preprocess(
    (val) =>
        val === '' || val === null || val === undefined ? null : Number(val),
    z.number().min(0).nullable(),
);

export const stockTransferFormSchema = z.object({
    transfer_number: z.string().optional(),
    from_warehouse_id: z.string().min(1, requiredMessage('From warehouse')),
    to_warehouse_id: z.string().min(1, requiredMessage('To warehouse')),
    transfer_date: z.string().min(1, requiredMessage('Transfer date')),
    expected_arrival_date: z.string().optional().nullable(),
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
                product_id: z.string().min(1, requiredMessage('Product')),
                product_label: z.string().optional(),
                unit_id: z.string().min(1, requiredMessage('Unit')),
                unit_label: z.string().optional(),
                quantity: z.coerce
                    .number()
                    .gt(0, { message: 'Quantity must be greater than 0.' }),
                quantity_received: stockTransferQuantityReceivedSchema,
                unit_cost: z.coerce.number().min(0).optional().default(0),
                notes: z.string().optional(),
            }),
        )
        .min(1, atLeastOneItemMessage),
});

export type StockTransferFormData = z.infer<typeof stockTransferFormSchema>;

export const inventoryStocktakeItemSchema = z.object({
    product_id: z.string().min(1, requiredMessage('Product')),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, requiredMessage('Unit')),
    unit_label: z.string().optional(),
    system_quantity: z.coerce
        .number()
        .min(0, { message: 'System quantity must be at least 0.' }),
    counted_quantity: z.coerce
        .number()
        .min(0, { message: 'Counted quantity must be at least 0.' })
        .optional()
        .default(0),
    notes: z.string().optional(),
});

export const inventoryStocktakeFormSchema = z.object({
    stocktake_number: z.string().optional(),
    warehouse_id: z.string().min(1, requiredMessage('Warehouse')),
    stocktake_date: z.string().min(1, requiredMessage('Stocktake date')),
    status: z.enum(['draft', 'in_progress', 'completed', 'cancelled'], {
        message: 'Status is required.',
    }),
    product_category_id: z.string().optional(),
    notes: z.string().optional(),
    items: z.array(inventoryStocktakeItemSchema).min(1, atLeastOneItemMessage),
});

export type InventoryStocktakeFormData = z.infer<
    typeof inventoryStocktakeFormSchema
>;

export const stockAdjustmentItemSchema = z.object({
    product_id: z.string().min(1, requiredMessage('Product')),
    product_label: z.string().optional(),
    unit_id: z.string().min(1, requiredMessage('Unit')),
    unit_label: z.string().optional(),
    quantity_before: z.coerce
        .number()
        .min(0, { message: 'Quantity before must be at least 0.' })
        .optional()
        .default(0),
    quantity_adjusted: z.coerce.number().refine((n) => n !== 0, {
        message: 'Quantity adjusted cannot be 0.',
    }),
    unit_cost: z.coerce
        .number()
        .min(0, { message: 'Unit cost must be at least 0.' })
        .optional()
        .default(0),
    reason: z.string().optional(),
});

export const stockAdjustmentFormSchema = z.object({
    adjustment_number: z.string().optional(),
    warehouse_id: z.string().min(1, requiredMessage('Warehouse')),
    adjustment_date: z.string().min(1, requiredMessage('Adjustment date')),
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
    items: z.array(stockAdjustmentItemSchema).min(1, atLeastOneItemMessage),
});

export type StockAdjustmentFormData = z.infer<typeof stockAdjustmentFormSchema>;

export const purchaseRequestFormSchema = z.object({
    pr_number: z.string().optional(),
    branch_id: z.string().min(1, { message: 'Branch is required.' }),
    department_id: z.string().optional(),
    requested_by: z.string().optional(),
    request_date: z.string().min(1, requiredMessage('Request date')),
    required_date: z.string().optional().nullable(),
    priority: z.enum(['low', 'normal', 'high', 'urgent'], {
        message: 'Priority is required.',
    }),
    status: z.enum(
        [
            'draft',
            'pending_approval',
            'approved',
            'rejected',
            'partially_ordered',
            'fully_ordered',
            'cancelled',
        ],
        {
            message: 'Status is required.',
        },
    ),
    estimated_amount: z.coerce.number().min(0).optional(),
    notes: z.string().optional(),
    rejection_reason: z.string().optional(),
    items: z
        .array(
            z.object({
                product_id: z.string().min(1, requiredMessage('Product')),
                unit_id: z.string().min(1, requiredMessage('Unit')),
                quantity: z.coerce
                    .number()
                    .gt(0, { message: 'Quantity must be greater than 0.' }),
                estimated_unit_price: z.coerce
                    .number()
                    .min(0)
                    .optional()
                    .default(0),
                notes: z.string().optional(),
            }),
        )
        .min(1, atLeastOneItemMessage),
});

export type PurchaseRequestFormData = z.infer<typeof purchaseRequestFormSchema>;

export const purchaseOrderFormSchema = z.object({
    po_number: z.string().optional(),
    supplier_id: z
        .string()
        .min(1, { message: 'validation.purchase_order.supplier_required' }),
    warehouse_id: z
        .string()
        .min(1, { message: 'validation.purchase_order.warehouse_required' }),
    order_date: z
        .string()
        .min(1, { message: 'validation.purchase_order.order_date_required' }),
    expected_delivery_date: z.string().optional().nullable(),
    payment_terms: z.string().optional(),
    currency: z
        .string()
        .min(3, { message: 'validation.purchase_order.currency_required' })
        .max(3),
    status: z.enum(
        [
            'draft',
            'pending_approval',
            'confirmed',
            'rejected',
            'partially_received',
            'fully_received',
            'cancelled',
            'closed',
        ],
        {
            message: 'validation.purchase_order.status_required',
        },
    ),
    notes: z.string().optional(),
    shipping_address: z.string().optional(),
    items: z
        .array(
            z.object({
                purchase_request_item_id: z.string().optional(),
                product_id: z.string().min(1, {
                    message: 'validation.purchase_order.product_required',
                }),
                unit_id: z.string().min(1, {
                    message: 'validation.purchase_order.unit_required',
                }),
                ...linePricingFields({
                    quantity: 'validation.purchase_order.quantity_gt_zero',
                    unitPrice: 'validation.purchase_order.unit_price_min_zero',
                }),
            }),
        )
        .min(1, { message: 'validation.purchase_order.items_min_one' }),
});

export type PurchaseOrderFormData = z.infer<typeof purchaseOrderFormSchema>;

export const goodsReceiptFormSchema = z.object({
    gr_number: z.string().optional(),
    purchase_order_id: z.string().min(1, requiredMessage('Purchase order')),
    warehouse_id: z.string().min(1, requiredMessage('Warehouse')),
    receipt_date: z.string().min(1, requiredMessage('Receipt date')),
    supplier_delivery_note: z.string().optional(),
    status: z.enum(['draft', 'confirmed', 'cancelled'], {
        message: 'Status is required.',
    }),
    received_by: z.string().optional(),
    notes: z.string().optional(),
    items: z
        .array(
            z.object({
                purchase_order_item_id: z
                    .string()
                    .min(1, { message: 'PO Item is required.' }),
                product_id: z.string().min(1, requiredMessage('Product')),
                unit_id: z.string().min(1, requiredMessage('Unit')),
                quantity_received: z.coerce.number().gt(0, {
                    message: 'Quantity received must be greater than 0.',
                }),
                quantity_accepted: z.coerce.number().min(0, {
                    message: 'Quantity accepted must be at least 0.',
                }),
                quantity_rejected: z.coerce
                    .number()
                    .min(0)
                    .optional()
                    .default(0),
                unit_price: z.coerce
                    .number()
                    .min(0, { message: 'Unit price must be at least 0.' }),
                notes: z.string().optional(),
            }),
        )
        .min(1, atLeastOneItemMessage),
});

export type GoodsReceiptFormData = z.infer<typeof goodsReceiptFormSchema>;

export const supplierReturnFormSchema = z.object({
    return_number: z.string().optional(),
    purchase_order_id: z.string().min(1, requiredMessage('Purchase order')),
    goods_receipt_id: z.string().optional(),
    supplier_id: z.string().min(1, requiredMessage('Supplier')),
    warehouse_id: z.string().min(1, requiredMessage('Warehouse')),
    return_date: z.string().min(1, requiredMessage('Return date')),
    reason: z.enum(
        ['defective', 'wrong_item', 'excess_quantity', 'damaged', 'other'],
        {
            message: 'Reason is required.',
        },
    ),
    status: z.enum(['draft', 'confirmed', 'cancelled'], {
        message: 'Status is required.',
    }),
    notes: z.string().optional(),
    items: z
        .array(
            z.object({
                goods_receipt_item_id: z
                    .string()
                    .min(1, { message: 'GR item is required.' }),
                product_id: z.string().min(1, requiredMessage('Product')),
                unit_id: z.string().optional(),
                quantity_returned: z.coerce.number().gt(0, {
                    message: 'Quantity returned must be greater than 0.',
                }),
                unit_price: z.coerce
                    .number()
                    .min(0, { message: 'Unit price must be at least 0.' }),
                notes: z.string().optional(),
            }),
        )
        .min(1, atLeastOneItemMessage),
});

export type SupplierReturnFormData = z.infer<typeof supplierReturnFormSchema>;

export const supplierBillFormSchema = z.object({
    bill_number: z.string().optional(),
    supplier_id: z
        .string()
        .min(1, { message: 'validation.supplier_bill.supplier_required' }),
    branch_id: z
        .string()
        .min(1, { message: 'validation.supplier_bill.branch_required' }),
    fiscal_year_id: z
        .string()
        .min(1, { message: 'validation.supplier_bill.fiscal_year_required' }),
    purchase_order_id: z.string().optional(),
    goods_receipt_id: z.string().optional(),
    supplier_invoice_number: z.string().optional(),
    supplier_invoice_date: z.string().nullable().optional(),
    bill_date: z
        .string()
        .min(1, { message: 'validation.supplier_bill.bill_date_required' }),
    due_date: z
        .string()
        .min(1, { message: 'validation.supplier_bill.due_date_required' }),
    payment_terms: z.string().optional(),
    currency: z
        .string()
        .min(1, { message: 'validation.supplier_bill.currency_required' }),
    status: z
        .string()
        .min(1, { message: 'validation.supplier_bill.status_required' }),
    notes: z.string().optional(),
    items: z
        .array(
            z.object({
                product_id: z.string().optional(),
                product_label: z.string().optional(),
                account_id: z.string().min(1, {
                    message: 'validation.supplier_bill.account_required',
                }),
                account_label: z.string().optional(),
                description: z.string().min(1, {
                    message: 'validation.supplier_bill.description_required',
                }),
                quantity: z.coerce.number().gt(0, {
                    message: 'validation.supplier_bill.quantity_gt_zero',
                }),
                unit_price: z.coerce.number().min(0),
                discount_percent: percentField(),
                tax_percent: percentField(),
                goods_receipt_item_id: z.string().optional(),
                notes: z.string().optional(),
            }),
        )
        .min(1, { message: 'validation.supplier_bill.items_min_one' }),
});

export type SupplierBillFormData = z.infer<typeof supplierBillFormSchema>;

export const apPaymentFormSchema = z.object({
    payment_number: z.string().optional(),
    supplier_id: z
        .string()
        .min(1, { message: 'validation.ap_payment.supplier_required' }),
    branch_id: z
        .string()
        .min(1, { message: 'validation.ap_payment.branch_required' }),
    fiscal_year_id: z
        .string()
        .min(1, { message: 'validation.ap_payment.fiscal_year_required' }),
    payment_date: z
        .string()
        .min(1, { message: 'validation.ap_payment.payment_date_required' }),
    payment_method: z
        .string()
        .min(1, { message: 'validation.ap_payment.payment_method_required' }),
    bank_account_id: z
        .string()
        .min(1, { message: 'validation.ap_payment.bank_account_required' }),
    currency: z
        .string()
        .min(1, { message: 'validation.ap_payment.currency_required' }),
    total_amount: z.coerce.number().gt(0, {
        message: 'validation.ap_payment.total_amount_gt_zero',
    }),
    reference: z.string().optional(),
    status: z
        .string()
        .min(1, { message: 'validation.ap_payment.status_required' }),
    notes: z.string().optional(),
    allocations: z
        .array(
            z.object({
                supplier_bill_id: z.string().min(1, {
                    message: 'validation.ap_payment.bill_required',
                }),
                bill_label: z.string().optional(),
                allocated_amount: z.coerce.number().gt(0, {
                    message: 'validation.ap_payment.amount_gt_zero',
                }),
                discount_taken: z.coerce.number().min(0).optional().default(0),
                notes: z.string().optional(),
            }),
        )
        .min(1, { message: 'validation.ap_payment.allocations_min_one' }),
});

export type ApPaymentFormData = z.infer<typeof apPaymentFormSchema>;

export const customerInvoiceFormSchema = z.object({
    customer_id: z
        .string()
        .min(1, { message: 'validation.customer_invoice.customer_required' }),
    branch_id: z
        .string()
        .min(1, { message: 'validation.customer_invoice.branch_required' }),
    fiscal_year_id: z.string().min(1, {
        message: 'validation.customer_invoice.fiscal_year_required',
    }),
    invoice_date: z.string().min(1, {
        message: 'validation.customer_invoice.invoice_date_required',
    }),
    due_date: z.string().min(1, {
        message: 'validation.customer_invoice.due_date_required',
    }),
    payment_terms: z.string().optional(),
    currency: z
        .string()
        .min(1, { message: 'validation.customer_invoice.currency_required' }),
    status: z.enum(
        [
            'draft',
            'sent',
            'partially_paid',
            'paid',
            'overdue',
            'cancelled',
            'void',
        ],
        { message: 'validation.customer_invoice.status_required' },
    ),
    notes: z.string().optional(),
    items: z
        .array(
            z.object({
                product_id: z.string().optional(),
                product_label: z.string().optional(),
                account_id: z.string().min(1, {
                    message: 'validation.customer_invoice.account_required',
                }),
                account_label: z.string().optional(),
                unit_id: z.string().optional(),
                unit_label: z.string().optional(),
                description: z.string().min(1, {
                    message: 'validation.customer_invoice.description_required',
                }),
                ...linePricingFields({
                    quantity: 'validation.customer_invoice.quantity_gt_zero',
                    unitPrice:
                        'validation.customer_invoice.unit_price_min_zero',
                }),
            }),
        )
        .min(1, { message: 'validation.customer_invoice.items_min_one' }),
});

export type CustomerInvoiceFormData = z.infer<typeof customerInvoiceFormSchema>;

export const arReceiptFormSchema = z.object({
    customer_id: z
        .string()
        .min(1, { message: 'validation.ar_receipt.customer_required' }),
    branch_id: z
        .string()
        .min(1, { message: 'validation.ar_receipt.branch_required' }),
    fiscal_year_id: z
        .string()
        .min(1, { message: 'validation.ar_receipt.fiscal_year_required' }),
    receipt_date: z
        .string()
        .min(1, { message: 'validation.ar_receipt.receipt_date_required' }),
    payment_method: z.enum(
        ['bank_transfer', 'cash', 'check', 'giro', 'credit_card', 'other'],
        { message: 'validation.ar_receipt.payment_method_required' },
    ),
    bank_account_id: z.string().optional(),
    currency: z
        .string()
        .min(1, { message: 'validation.ar_receipt.currency_required' }),
    total_amount: z.coerce.number().gt(0, {
        message: 'validation.ar_receipt.total_amount_gt_zero',
    }),
    reference: z.string().optional(),
    status: z.enum(['draft', 'confirmed', 'reconciled', 'cancelled', 'void'], {
        message: 'validation.ar_receipt.status_required',
    }),
    notes: z.string().optional(),
    allocations: z
        .array(
            z.object({
                customer_invoice_id: z.string().min(1, {
                    message: 'validation.ar_receipt.invoice_required',
                }),
                invoice_label: z.string().optional(),
                allocated_amount: z.coerce.number().gt(0, {
                    message: 'validation.ar_receipt.allocated_amount_gt_zero',
                }),
                discount_given: z.coerce.number().min(0).optional().default(0),
                notes: z.string().optional(),
            }),
        )
        .min(1, { message: 'validation.ar_receipt.allocations_min_one' }),
});

export type ArReceiptFormData = z.infer<typeof arReceiptFormSchema>;

export const creditNoteFormSchema = z.object({
    customer_id: z
        .string()
        .min(1, { message: 'validation.credit_note.customer_required' }),
    customer_invoice_id: z.string().optional(),
    branch_id: z
        .string()
        .min(1, { message: 'validation.credit_note.branch_required' }),
    fiscal_year_id: z
        .string()
        .min(1, { message: 'validation.credit_note.fiscal_year_required' }),
    credit_note_date: z.string().min(1, {
        message: 'validation.credit_note.credit_note_date_required',
    }),
    reason: z.enum(['return', 'discount', 'correction', 'bad_debt', 'other'], {
        message: 'validation.credit_note.reason_required',
    }),
    status: z.enum(['draft', 'confirmed', 'applied', 'cancelled', 'void'], {
        message: 'validation.credit_note.status_required',
    }),
    notes: z.string().optional(),
    items: z
        .array(
            z.object({
                product_id: z.string().optional(),
                product_label: z.string().optional(),
                account_id: z.string().min(1, {
                    message: 'validation.credit_note.account_required',
                }),
                account_label: z.string().optional(),
                description: z.string().min(1, {
                    message: 'validation.credit_note.description_required',
                }),
                quantity: z.coerce.number().gt(0, {
                    message: 'validation.credit_note.quantity_gt_zero',
                }),
                unit_price: z.coerce.number().min(0, {
                    message: 'validation.credit_note.unit_price_min_zero',
                }),
                tax_percent: z.coerce
                    .number()
                    .min(0)
                    .max(100)
                    .optional()
                    .default(0),
                notes: z.string().optional(),
            }),
        )
        .min(1, { message: 'validation.credit_note.items_min_one' }),
});

export type CreditNoteFormData = z.infer<typeof creditNoteFormSchema>;

export const transactionItemPricingSchema = {
    quantity: z.coerce
        .number()
        .gt(0, { message: 'Quantity must be greater than 0.' }),
    unit_price: z.coerce
        .number()
        .min(0, { message: 'Unit price must be at least 0.' }),
    discount_percent: z.coerce.number().min(0).max(100).optional().default(0),
    tax_percent: z.coerce.number().min(0).max(100).optional().default(0),
};

export const budgetLineFormSchema = z.object({
    id: z.number().optional(),
    account_id: z.coerce.number().min(1, { message: 'Account is required.' }),
    account_name: z.string().optional(),
    account_code: z.string().optional(),
    period_start: z.string().min(1, { message: 'Period start is required.' }),
    period_end: z.string().min(1, { message: 'Period end is required.' }),
    allocated_amount: z.coerce
        .number()
        .min(0, { message: 'Amount must be non-negative.' }),
    notes: z.string().nullable().optional(),
});

export const budgetFormSchema = z.object({
    name: z.string().min(1, { message: 'validation.budget.name_required' }),
    description: z.string().nullable().optional(),
    fiscal_year_id: z.coerce
        .number()
        .min(1, { message: 'validation.budget.fiscal_year_required' }),
    budget_type: z.enum(['operational', 'capital', 'project', 'revenue']),
    lines: z
        .array(budgetLineFormSchema)
        .min(1, { message: 'validation.budget.lines_min_one' }),
});

export type BudgetLineFormData = z.infer<typeof budgetLineFormSchema>;
export type BudgetFormData = z.infer<typeof budgetFormSchema>;
