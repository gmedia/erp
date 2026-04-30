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
    entity?: Customer | null;
    onSubmit: (data: CustomerFormData) => void;
    isLoading?: boolean;
}

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
            name="branch_id"
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
    entity?: Customer | null,
): CustomerFormData => {
    if (!entity) {
        return {
            name: '',
            email: '',
            phone: '',
            address: '',
            branch_id: '',
            category_id: '',
            status: 'active',
            notes: '',
        };
    }

    return {
        name: entity.name,
        email: entity.email,
        phone: entity.phone || '',
        address: entity.address,
        branch_id:
            typeof entity.branch === 'object'
                ? String(entity.branch.id)
                : String(entity.branch),
        category_id:
            typeof entity.category === 'object'
                ? String(entity.category.id)
                : String(entity.category_id),
        status: entity.status,
        notes: entity.notes || '',
    };
};

export const CustomerForm = memo<CustomerFormProps>(function CustomerForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getCustomerFormDefaults(entity),
        [entity],
    );

    const form = useForm<CustomerFormData>({
        resolver: zodResolver(customerFormSchema),
        defaultValues,
    });

    // Reset form when entity changes (for edit mode)
    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<CustomerFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Customer' : 'Add New Customer'}
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
