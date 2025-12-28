'use client';

import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { InputField } from '@/components/common/InputField';
import { DatePickerField } from '@/components/common/DatePickerField';

import { DEPARTMENTS, POSITIONS } from '@/constants';
import { Employee, EmployeeFormData } from '@/types/entity';
import { employeeFormSchema } from '@/utils/schemas';

interface EmployeeFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    employee?: Employee | null;
    onSubmit: (data: EmployeeFormData) => void;
    isLoading?: boolean;
}

/**
 * Helper function to get default values for employee form
 */
const getEmployeeFormDefaults = (employee?: Employee | null): EmployeeFormData => {
    if (!employee) {
        return {
            name: '',
            email: '',
            phone: '',
            department: '',
            position: '',
            salary: '',
            hire_date: new Date(),
        };
    }

    return {
        name: employee.name,
        email: employee.email,
        phone: employee.phone,
        department: employee.department,
        position: employee.position,
        salary: employee.salary,
        hire_date: new Date(employee.hire_date),
    };
};

export function EmployeeForm({
    open,
    onOpenChange,
    employee,
    onSubmit,
    isLoading = false,
}: EmployeeFormProps) {
    const form = useForm<EmployeeFormData>({
        resolver: zodResolver(employeeFormSchema),
        defaultValues: getEmployeeFormDefaults(employee),
    });

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={employee ? 'Edit Employee' : 'Add New Employee'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label="Name"
                placeholder="John Doe"
            />

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
