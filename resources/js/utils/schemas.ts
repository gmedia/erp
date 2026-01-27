import * as z from 'zod';

// Schema for simple entities (departments, positions)
export const simpleEntitySchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
});

export type SimpleEntityFormData = z.infer<typeof simpleEntitySchema>;

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
