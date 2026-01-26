'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';

import { Customer, CustomerFormData } from '@/types/entity';
import { customerFormSchema } from '@/utils/schemas';

interface CustomerFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    customer?: Customer | null;
    onSubmit: (data: CustomerFormData) => void;
    isLoading?: boolean;
}

const customerTypeOptions = [
    { value: 'individual', label: 'Individual' },
    { value: 'company', label: 'Company' },
];

// Status options
const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

/**
 * Customer form sections for better organization and maintainability
 */
const renderCustomerBasicInfoSection = () => (
    <>
        <NameField name="name" label="Name" placeholder="Customer name" />
        <InputField
            name="email"
            label="Email"
            type="email"
            placeholder="customer@example.com"
        />
        <InputField
            name="phone"
            label="Phone"
            placeholder="+62 812 3456 7890"
        />
    </>
);

const renderCustomerAddressSection = () => (
    <TextareaField
        name="address"
        label="Address"
        placeholder="Full address"
        rows={3}
    />
);

const renderCustomerDetailsSection = () => (
    <>
        <AsyncSelectField
            name="branch"
            label="Branch"
            url="/api/branches"
            placeholder="Select a branch"
        />
        <AsyncSelectField
            name="category_id"
            label="Category"
            url="/api/customer-categories"
            placeholder="Select a category"
        />
        <SelectField
            name="status"
            label="Status"
            options={statusOptions}
            placeholder="Select status"
        />
    </>
);

const renderCustomerNotesSection = () => (
    <TextareaField
        name="notes"
        label="Notes"
        placeholder="Additional notes (optional)"
        rows={2}
    />
);

/**
 * Helper function to get default values for customer form
 */
const getCustomerFormDefaults = (
    customer?: Customer | null,
): CustomerFormData => {
    if (!customer) {
        return {
            name: '',
            email: '',
            phone: '',
            address: '',
            branch: '',
            category_id: '',
            status: 'active',
            notes: '',
        };
    }

    return {
        name: customer.name,
        email: customer.email,
        phone: customer.phone || '',
        address: customer.address,
        branch:
            typeof customer.branch === 'object'
                ? String(customer.branch.id)
                : customer.branch,
        category_id:
            typeof customer.category === 'object'
                ? String(customer.category.id)
                : String(customer.category_id),
        status: customer.status,
        notes: customer.notes || '',
    };
};

export const CustomerForm = memo<CustomerFormProps>(function CustomerForm({
    open,
    onOpenChange,
    customer,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getCustomerFormDefaults(customer),
        [customer],
    );

    const form = useForm<CustomerFormData>({
        resolver: zodResolver(customerFormSchema),
        defaultValues,
    });

    // Reset form when customer changes (for edit mode)
    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<CustomerFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={customer ? 'Edit Customer' : 'Add New Customer'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            {renderCustomerBasicInfoSection()}
            {renderCustomerAddressSection()}
            {renderCustomerDetailsSection()}
            {renderCustomerNotesSection()}
        </EntityForm>
    );
});
