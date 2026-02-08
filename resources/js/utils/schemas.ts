import * as z from 'zod';

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
    symbol: z.string().max(10, { message: 'Symbol cannot exceed 10 characters' }).optional().nullable(),
});

export type UnitFormData = z.infer<typeof unitFormSchema>;

// Schema for employee form data
export const employeeFormSchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    email: z.string().email({ message: 'Please enter a valid email address.' }),
    phone: z
        .string()
        .min(10, { message: 'Phone number must be at least 10 digits.' })
        .regex(/^[\d\s\-+().]+$/, {
            message: 'Please enter a valid phone number.',
        }),
    department_id: z.string().min(1, { message: 'Department is required.' }),
    position_id: z.string().min(1, { message: 'Position is required.' }),
    branch_id: z.string().min(1, { message: 'Branch is required.' }),
    salary: z
        .string()
        .min(1, { message: 'Salary is required.' })
        .transform((val) => val.replace(/[,\s]/g, ''))
        .pipe(
            z.string().regex(/^\d+(\.\d{1,2})?$/, {
                message:
                    'Please enter a valid salary amount (e.g., 50000 or 50000.00).',
            }),
        ),
    hire_date: z.date({ message: 'Hire date is required.' }),
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
    address: z.string().min(5, { message: 'Address must be at least 5 characters.' }),
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
    email: z.string().email({ message: 'Please enter a valid email address.' }),
    phone: z
        .string()
        .min(10, { message: 'Phone number must be at least 10 digits.' })
        .regex(/^[\d\s\-+().]+$/, {
            message: 'Please enter a valid phone number.',
        })
        .or(z.literal('')),
    address: z.string().min(5, { message: 'Address must be at least 5 characters.' }),
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
    type: z.enum(['raw_material', 'work_in_progress', 'finished_good', 'purchased_good', 'service'], {
        message: 'Product type is required.',
    }),
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
    lines: z.array(z.object({
        account_id: z.string().min(1, { message: 'Account is required.' }),
        debit: z.coerce.number().min(0),
        credit: z.coerce.number().min(0),
        memo: z.string().optional(),
    })).min(2, { message: 'At least 2 lines are required.' })
    .refine((lines) => {
        const totalDebit = lines.reduce((sum, line) => sum + (line.debit || 0), 0);
        const totalCredit = lines.reduce((sum, line) => sum + (line.credit || 0), 0);
        return Math.abs(totalDebit - totalCredit) < 0.01;
    }, { message: 'Total Debit and Total Credit must be equal.', path: ['root'] }),
});

export type JournalEntryFormData = z.infer<typeof journalEntryFormSchema>;

/**
 * Asset category form schema with code, name, and useful life.
 */
export const assetCategoryFormSchema = z.object({
    code: z.string().min(2, { message: 'Code must be at least 2 characters.' }),
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    useful_life_months_default: z.coerce.number().min(1, { message: 'Useful life must be at least 1 month.' }),
});

export type AssetCategoryFormData = z.infer<typeof assetCategoryFormSchema>;

/**
 * Asset model form schema.
 */
export const assetModelFormSchema = z.object({
    model_name: z.string().min(2, { message: 'Model name must be at least 2 characters.' }),
    manufacturer: z.string().optional(),
    asset_category_id: z.string().min(1, { message: 'Category is required.' }),
    specs: z.string().optional().refine((val) => {
        if (!val || val.trim() === '') return true;
        try {
            JSON.parse(val);
            return true;
        } catch {
            return false;
        }
    }, { message: 'Specs must be valid JSON.' }),
});

export type AssetModelFormData = z.infer<typeof assetModelFormSchema>;

export const accountMappingFormSchema = z
    .object({
        source_coa_version_id: z.string().min(1, { message: 'Source COA Version is required.' }),
        target_coa_version_id: z.string().min(1, { message: 'Target COA Version is required.' }),
        source_account_id: z.string().min(1, { message: 'Source account is required.' }),
        target_account_id: z.string().min(1, { message: 'Target account is required.' }),
        type: z.enum(['merge', 'split', 'rename'], { message: 'Type is required.' }),
        notes: z.string().optional(),
    })
    .refine(
        (data) => data.source_coa_version_id !== data.target_coa_version_id,
        { message: 'Source and target COA versions must be different.', path: ['target_coa_version_id'] },
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

/**
 * Asset form schema.
 */
export const assetFormSchema = z.object({
    asset_code: z.string().min(2, { message: 'Asset code must be at least 2 characters.' }),
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
    condition: z.enum(['good', 'needs_repair', 'damaged']).optional().nullable(),
    notes: z.string().default(''),
    depreciation_method: z.enum(['straight_line', 'declining_balance']).default('straight_line'),
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
    movement_type: z.enum(['transfer', 'assign', 'return', 'dispose', 'adjustment'], {
        message: 'Movement type is required.',
    }),
    moved_at: z.date({ message: 'Movement date is required.' }),
    to_branch_id: z.string().optional(),
    to_location_id: z.string().optional(),
    to_department_id: z.string().optional(),
    to_employee_id: z.string().optional(),
    reference: z.string().optional(),
    notes: z.string().optional(),
});

export type AssetMovementFormData = z.infer<typeof assetMovementFormSchema>;
