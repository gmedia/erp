'use client';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { InputField } from '@/components/common/InputField';
import { DatePickerField } from '@/components/common/DatePickerField';
import { DEPARTMENTS, POSITIONS } from '@/constants';
import { Employee, EmployeeFormData } from '@/types/entity';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

const formSchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
    email: z.string().email({ message: 'Please enter a valid email address.' }),
    phone: z.string().min(10, { message: 'Phone number must be at least 10 digits.' }),
    department: z.string().min(2, { message: 'Department must be at least 2 characters.' }),
    position: z.string().min(2, { message: 'Position must be at least 2 characters.' }),
    salary: z.string().regex(/^\d+$/, { message: 'Please enter a valid salary amount.' }),
    hire_date: z.date({ message: 'Hire date is required.' }),
});

interface EmployeeFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    employee?: Employee | null;
    onSubmit: (data: EmployeeFormData) => void;
    isLoading?: boolean;
}

export function EmployeeForm({
    open,
    onOpenChange,
    employee,
    onSubmit,
    isLoading = false,
}: EmployeeFormProps) {
    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: employee
            ? {
                  name: employee.name,
                  email: employee.email,
                  phone: employee.phone,
                  department: employee.department,
                  position: employee.position,
                  salary: employee.salary,
                  hire_date: new Date(employee.hire_date),
              }
            : {
                  name: '',
                  email: '',
                  phone: '',
                  department: '',
                  position: '',
                  salary: '',
                  hire_date: new Date(),
              },
    });

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={employee ? 'Edit Employee' : 'Add New Employee'}
            onSubmit={onSubmit}
            schema={formSchema}
            isLoading={isLoading}
        >
            <NameField name="name" label="Name" placeholder="John Doe" />

            <InputField
                control={form.control}
                name="email"
                label="Email"
                type="email"
                placeholder="john.doe@example.com"
            />

            <InputField
                control={form.control}
                name="phone"
                label="Phone"
                placeholder="+1 (555) 123-4567"
            />

            <SelectField
                name="department"
                label="Department"
                options={DEPARTMENTS}
                placeholder="Select a department"
            />

            <SelectField
                name="position"
                label="Position"
                options={POSITIONS}
                placeholder="Select a position"
            />

            <InputField
                control={form.control}
                name="salary"
                label="Salary"
                type="number"
                placeholder="50000"
            />

            <DatePickerField
                control={form.control}
                name="hire_date"
                label="Hire Date"
                placeholder="Pick a date"
                disabled={(date: Date) => date > new Date() || date < new Date('1900-01-01')}
            />
        </EntityForm>
    );
}
