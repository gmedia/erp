'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { memo, useCallback, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';

import { Employee } from '@/types/entity';
import {
    employeeFormSchema,
    type EmployeeFormData,
} from '@/utils/schemas';

interface EmployeeFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Employee | null;
    onSubmit: (data: Record<string, unknown>) => void;
    isLoading?: boolean;
}

interface BranchOption {
    id: number;
    name: string;
    company_id?: number | null;
}

type EmployeeFormInput = z.input<typeof employeeFormSchema>;

const emptyDefaults = (): EmployeeFormInput => ({
    employee_id: '',
    name: '',
    email: '',
    phone: '',
    company_id: '',
    department_id: '',
    position_id: '',
    branch_id: '',
    salary: '',
    hire_date: new Date(),
    employment_status: 'regular',
    termination_date: null,
});

const getEmployeeFormDefaults = (
    employee?: Employee | null,
): EmployeeFormInput => {
    if (!employee) {
        return emptyDefaults();
    }

    const employment = employee.current_employment;

    return {
        employee_id: employee.employee_id || '',
        name: employee.name,
        email: employee.email,
        phone: employee.phone || '',
        company_id: employment?.company_id ? String(employment.company_id) : '',
        department_id: employment?.department_id
            ? String(employment.department_id)
            : '',
        position_id: employment?.position_id
            ? String(employment.position_id)
            : '',
        branch_id: employment?.branch_id ? String(employment.branch_id) : '',
        salary: employment?.salary || '',
        hire_date: employment?.hire_date
            ? new Date(employment.hire_date)
            : new Date(),
        employment_status: employment?.employment_status || 'regular',
        termination_date: employment?.termination_date
            ? new Date(employment.termination_date)
            : null,
    };
};

const toApiPayload = (data: EmployeeFormData): Record<string, unknown> => {
    const hireDate = format(data.hire_date, 'yyyy-MM-dd');

    const terminationDate = data.termination_date
        ? format(data.termination_date, 'yyyy-MM-dd')
        : null;

    return {
        employee_id: data.employee_id,
        name: data.name,
        email: data.email,
        phone: data.phone || null,
        current_employment: {
            company_id: Number(data.company_id),
            department_id: Number(data.department_id),
            position_id: Number(data.position_id),
            branch_id: Number(data.branch_id),
            salary: data.salary ? data.salary : null,
            hire_date: hireDate,
            employment_status: data.employment_status,
            termination_date: terminationDate,
        },
    };
};

const renderEmployeeBasicInfoSection = () => (
    <>
        <InputField
            name="employee_id"
            label="Employee ID (NIK)"
            placeholder="EMP-001"
        />
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

const renderEmployeeHireDateSection = () => (
    <div className="flex gap-4">
        <div className="flex-1">
            <DatePickerField
                name="hire_date"
                label="Hire Date"
                placeholder="Pick a date"
                disabled={(date: Date) =>
                    date > new Date() || date < new Date('1900-01-01')
                }
            />
        </div>
        <div className="flex-1">
            <DatePickerField
                name="termination_date"
                label="Termination Date (Optional)"
                placeholder="Pick a date"
                disabled={(date: Date) => date < new Date('1900-01-01')}
            />
        </div>
    </div>
);

export const EmployeeForm = memo<EmployeeFormProps>(function EmployeeForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getEmployeeFormDefaults(entity),
        [entity],
    );

    const form = useForm<EmployeeFormInput, unknown, EmployeeFormData>({
        resolver: zodResolver(employeeFormSchema),
        defaultValues,
    });

    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    const handleBranchSelect = useCallback(
        (item: BranchOption) => {
            if (item.company_id != null) {
                form.setValue('company_id', String(item.company_id), {
                    shouldValidate: true,
                    shouldDirty: true,
                });
            }
        },
        [form],
    );

    const handleFormSubmit = (data: EmployeeFormData) => {
        onSubmit(toApiPayload(data));
    };

    return (
        <EntityForm<EmployeeFormInput, EmployeeFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Employee' : 'Add New Employee'}
            onSubmit={handleFormSubmit}
            isLoading={isLoading}
        >
            {renderEmployeeBasicInfoSection()}
            <SelectField
                name="employment_status"
                label="Employment Status"
                options={[
                    { value: 'regular', label: 'Regular' },
                    { value: 'intern', label: 'Intern' },
                ]}
            />
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
            <AsyncSelectField<BranchOption>
                name="branch_id"
                label="Branch"
                url="/api/branches"
                placeholder="Select a branch"
                onItemSelect={handleBranchSelect}
            />
            <input type="hidden" {...form.register('company_id')} />
            <InputField
                name="salary"
                label="Salary (Optional)"
                type="number"
                placeholder="50000"
                prefix="Rp"
            />
            {renderEmployeeHireDateSection()}
        </EntityForm>
    );
});
