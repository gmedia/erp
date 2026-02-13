'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import { format } from 'date-fns';

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
            name="department_id"
            label="Department"
            url="/api/departments"
            placeholder="Select a department"
        />
        <AsyncSelectField
            name="position_id"
            label="Position"
            url="/api/positions"
            placeholder="Select a position"
        />
        <AsyncSelectField
            name="branch_id"
            label="Branch"
            url="/api/branches"
            placeholder="Select a branch"
        />
        <InputField
            name="salary"
            label="Salary"
            type="number"
            placeholder="50000"
            prefix="Rp"
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
            department_id: '',
            position_id: '',
            branch_id: '',
            salary: '',
            hire_date: new Date(),
        };
    }

    return {
        name: employee.name,
        email: employee.email,
        phone: employee.phone,
        department_id:
            typeof employee.department === 'object'
                ? String(employee.department.id)
                : String(employee.department),
        position_id:
            typeof employee.position === 'object'
                ? String(employee.position.id)
                : String(employee.position),
        branch_id:
            typeof employee.branch === 'object'
                ? String(employee.branch.id)
                : String(employee.branch),
        salary: employee.salary || '',
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

    const handleFormSubmit = (data: EmployeeFormData) => {
        onSubmit({
            ...data,
            hire_date: format(data.hire_date, 'yyyy-MM-dd') as any,
        });
    };

    return (
        <EntityForm<EmployeeFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={employee ? 'Edit Employee' : 'Add New Employee'}
            onSubmit={handleFormSubmit}
            isLoading={isLoading}
        >
            {renderEmployeeBasicInfoSection()}
            {renderEmployeeWorkInfoSection()}
            {renderEmployeeHireDateSection()}
        </EntityForm>
    );
});
