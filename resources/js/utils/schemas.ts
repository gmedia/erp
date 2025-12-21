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
    phone: z.string().min(10, { message: 'Phone number must be at least 10 digits.' }),
    department: z.string().min(1, { message: 'Department is required.' }),
    position: z.string().min(1, { message: 'Position is required.' }),
    salary: z.string().regex(/^\d+(\.\d{1,2})?$/, { message: 'Please enter a valid salary amount.' }),
    hire_date: z.date({ message: 'Hire date is required.' }),
});

export type EmployeeFormDataSchema = z.infer<typeof employeeFormSchema>;
