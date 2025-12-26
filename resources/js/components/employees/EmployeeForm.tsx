'use client';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { InputField } from '@/components/common/InputField';
import { DatePickerField } from '@/components/common/DatePickerField';
import { DEPARTMENTS, POSITIONS } from '@/constants';
import { Employee, EmployeeFormData } from '@/types/entity';
import { employeeFormSchema } from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm, UseFormReturn } from 'react-hook-form';

// Standardized form field configuration
interface FormFieldConfig {
    name: keyof EmployeeFormData;
    label: string;
    placeholder?: string;
    type?: 'text' | 'email' | 'number';
    component: 'name' | 'input' | 'select' | 'date';
    options?: Array<{ value: string; label: string }>;
    disabled?: (date: Date) => boolean;
}

// Employee form field configuration
const EMPLOYEE_FORM_FIELDS: FormFieldConfig[] = [
    {
        name: 'name',
        label: 'Name',
        placeholder: 'John Doe',
        component: 'name',
    },
    {
        name: 'email',
        label: 'Email',
        type: 'email',
        placeholder: 'john.doe@example.com',
        component: 'input',
    },
    {
        name: 'phone',
        label: 'Phone',
        placeholder: '+1 (555) 123-4567',
        component: 'input',
    },
    {
        name: 'department',
        label: 'Department',
        placeholder: 'Select a department',
        component: 'select',
        options: DEPARTMENTS,
    },
    {
        name: 'position',
        label: 'Position',
        placeholder: 'Select a position',
        component: 'select',
        options: POSITIONS,
    },
    {
        name: 'salary',
        label: 'Salary',
        type: 'number',
        placeholder: '50000',
        component: 'input',
    },
    {
        name: 'hire_date',
        label: 'Hire Date',
        placeholder: 'Pick a date',
        component: 'date',
        disabled: (date: Date) => date > new Date() || date < new Date('1900-01-01'),
    },
];

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
function getEmployeeFormDefaults(employee?: Employee | null): EmployeeFormData {
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
}

/**
 * Generic form field renderer that uses configuration
 */
function renderFormField(config: FormFieldConfig, form: UseFormReturn<EmployeeFormData>) {
    const { name, label, placeholder, type, component, options, disabled } = config;

    switch (component) {
        case 'name':
            return (
                <NameField
                    key={name}
                    name={name}
                    label={label}
                    placeholder={placeholder}
                />
            );

        case 'input':
            return (
                <InputField
                    key={name}
                    control={form.control}
                    name={name}
                    label={label}
                    type={type || 'text'}
                    placeholder={placeholder}
                />
            );

        case 'select':
            return (
                <SelectField
                    key={name}
                    name={name}
                    label={label}
                    options={options || []}
                    placeholder={placeholder}
                />
            );

        case 'date':
            return (
                <DatePickerField
                    key={name}
                    control={form.control}
                    name={name}
                    label={label}
                    placeholder={placeholder}
                    disabled={disabled}
                />
            );

        default:
            return null;
    }
}

export function EmployeeForm({
    open,
    onOpenChange,
    employee,
    onSubmit,
    isLoading = false,
}: EmployeeFormProps) {
    const form: UseFormReturn<EmployeeFormData> = useForm<EmployeeFormData>({
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
            {EMPLOYEE_FORM_FIELDS.map((fieldConfig) =>
                renderFormField(fieldConfig, form)
            )}
        </EntityForm>
    );
}
