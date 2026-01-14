'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';

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
 * Employee form sections for better organization and maintainability
 */
const renderEmployeeBasicInfoSection = () => (
    <>
        <NameField name="name" label="Name" placeholder="John Doe" />
        <InputField
            name="email"
            label="Email"
            type="email"
            placeholder="john.doe@example.com"
        />
        <InputField
            name="phone"
            label="Phone"
            placeholder="+1 (555) 123-4567"
        />
    </>
);

const renderEmployeeWorkInfoSection = () => (
    <>
        <AsyncSelectField
            name="department"
            label="Department"
            url="/api/departments"
            placeholder="Select a department"
        />
        <AsyncSelectField
            name="position"
            label="Position"
            url="/api/positions"
            placeholder="Select a position"
        />
        <InputField
            name="salary"
            label="Salary"
            type="number"
            placeholder="50000"
        />
    </>
);

const renderEmployeeHireDateSection = () => (
    <DatePickerField
        name="hire_date"
        label="Hire Date"
        placeholder="Pick a date"
        disabled={(date: Date) =>
            date > new Date() || date < new Date('1900-01-01')
        }
    />
);

/**
 * Helper function to get default values for employee form
 */
const getEmployeeFormDefaults = (
    employee?: Employee | null,
): EmployeeFormData => {
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

export const EmployeeForm = memo<EmployeeFormProps>(function EmployeeForm({
    open,
    onOpenChange,
    employee,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getEmployeeFormDefaults(employee),
        [employee],
    );

    const form = useForm<EmployeeFormData>({
        resolver: zodResolver(employeeFormSchema),
        defaultValues,
    });

    // Reset form when employee changes (for edit mode)
    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={employee ? 'Edit Employee' : 'Add New Employee'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            {renderEmployeeBasicInfoSection()}
            {renderEmployeeWorkInfoSection()}
            {renderEmployeeHireDateSection()}
        </EntityForm>
    );
});
