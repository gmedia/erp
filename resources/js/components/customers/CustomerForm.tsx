'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { useEntityForm } from '@/hooks/useEntityForm';

import { Customer, CustomerFormData } from '@/types/entity';
import { customerFormSchema } from '@/utils/schemas';

interface CustomerFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Customer | null;
    onSubmit: (data: CustomerFormData) => void;
    isLoading?: boolean;
}

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

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
    const form = useEntityForm<CustomerFormData, Customer>({
        schema: customerFormSchema,
        getDefaults: getCustomerFormDefaults,
        entity,
    });

    return (
        <EntityForm<CustomerFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Customer' : 'Add New Customer'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
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
            <TextareaField
                name="address"
                label="Address"
                placeholder="Full address"
                rows={3}
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
                url="/api/customer-categories"
                placeholder="Select a category"
            />
            <SelectField
                name="status"
                label="Status"
                options={statusOptions}
                placeholder="Select status"
            />
            <TextareaField
                name="notes"
                label="Notes"
                placeholder="Additional notes (optional)"
                rows={2}
            />
        </EntityForm>
    );
});
