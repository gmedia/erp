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

import { Supplier, SupplierFormData } from '@/types/entity';
import { supplierFormSchema } from '@/utils/schemas';

interface SupplierFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    supplier?: Supplier | null;
    onSubmit: (data: SupplierFormData) => void;
    isLoading?: boolean;
}

const renderBasicInfoSection = () => (
    <>
        <NameField name="name" label="Name" placeholder="Supplier Name" />
        <InputField
            name="email"
            label="Email"
            type="email"
            placeholder="supplier@example.com"
        />
        <InputField
            name="phone"
            label="Phone"
            placeholder="+1 (555) 123-4567"
        />
    </>
);

const renderDetailsSection = () => (
    <>
         <AsyncSelectField
            name="branch"
            label="Branch"
            url="/api/branches"
            placeholder="Select a branch"
        />
        <SelectField
            name="category"
            label="Category"
            options={[
                { value: 'electronics', label: 'Electronics' },
                { value: 'furniture', label: 'Furniture' },
                { value: 'stationery', label: 'Stationery' },
                { value: 'services', label: 'Services' },
                { value: 'other', label: 'Other' },
            ]}
            placeholder="Select a category"
        />
         <SelectField
            name="status"
            label="Status"
            options={[
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
            ]}
            placeholder="Select status"
        />
    </>
);

const renderAddressSection = () => (
    <TextareaField
        name="address"
        label="Address"
        placeholder="123 Main St, City, Country"
    />
);

const getSupplierFormDefaults = (
    supplier?: Supplier | null,
): SupplierFormData => {
    if (!supplier) {
        return {
            name: '',
            email: '',
            phone: '',
            address: '',
            branch: '',
            category: 'other',
            status: 'active',
        };
    }

    return {
        name: supplier.name,
        email: supplier.email,
        phone: supplier.phone || '',
        address: supplier.address,
        branch: supplier.branch ? String(supplier.branch.id) : '',
        category: supplier.category,
        status: supplier.status,
    };
};

export const SupplierForm = memo<SupplierFormProps>(function SupplierForm({
    open,
    onOpenChange,
    supplier,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getSupplierFormDefaults(supplier),
        [supplier],
    );

    const form = useForm<SupplierFormData>({
        resolver: zodResolver(supplierFormSchema),
        defaultValues,
    });

    // Reset form when supplier changes (for edit mode)
    useEffect(() => {
        form.reset(defaultValues);
    }, [form, defaultValues]);

    return (
        <EntityForm<SupplierFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={supplier ? 'Edit Supplier' : 'Add New Supplier'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            {renderBasicInfoSection()}
            {renderDetailsSection()}
            {renderAddressSection()}
        </EntityForm>
    );
});
