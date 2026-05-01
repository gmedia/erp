'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { useEntityForm } from '@/hooks/useEntityForm';

import { Supplier, SupplierFormData } from '@/types/entity';
import { supplierFormSchema } from '@/utils/schemas';

interface SupplierFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Supplier | null;
    onSubmit: (data: SupplierFormData) => void;
    isLoading?: boolean;
}

const getSupplierFormDefaults = (
    entity?: Supplier | null,
): SupplierFormData => {
    if (!entity) {
        return {
            name: '',
            email: '',
            phone: '',
            address: '',
            branch_id: '',
            category_id: '',
            status: 'active',
        };
    }

    return {
        name: entity.name,
        email: entity.email,
        phone: entity.phone || '',
        address: entity.address,
        branch_id: entity.branch ? String(entity.branch.id) : '',
        category_id: entity.category ? String(entity.category.id) : '',
        status: entity.status,
    };
};

export const SupplierForm = memo<SupplierFormProps>(function SupplierForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const form = useEntityForm<SupplierFormData, Supplier>({
        schema: supplierFormSchema,
        getDefaults: getSupplierFormDefaults,
        entity,
    });

    return (
        <EntityForm<SupplierFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Supplier' : 'Add New Supplier'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
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
            <AsyncSelectField
                name="branch_id"
                label="Branch"
                url="/api/branches"
                placeholder="Select a branch"
            />
            <AsyncSelectField
                name="category_id"
                label="Category"
                url="/api/supplier-categories"
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
            <TextareaField
                name="address"
                label="Address"
                placeholder="123 Main St, City, Country"
            />
        </EntityForm>
    );
});
