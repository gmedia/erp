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
    department: z.string().min(1, { message: 'Department is required.' }),
    position: z.string().min(1, { message: 'Position is required.' }),
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
