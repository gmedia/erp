'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { TextareaField } from '@/components/common/TextareaField';
import { useTranslation } from '@/contexts/i18n-context';
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

const ft = (key: string, fallback: string) => ({
    key: `form_fields.suppliers.${key}`,
    fallback,
});

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
    const { t } = useTranslation();
    const tf = (key: string, fallback: string) => {
        const translated = t(`form_fields.suppliers.${key}`);
        return translated === `form_fields.suppliers.${key}`
            ? fallback
            : translated;
    };
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
            title={
                entity
                    ? tf('edit_title', 'Edit Supplier')
                    : tf('add_title', 'Add New Supplier')
            }
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label={ft('name', 'Name')}
                placeholder={ft('name_placeholder', 'Supplier Name')}
            />
            <InputField
                name="email"
                label={ft('email', 'Email')}
                type="email"
                placeholder={ft('email_placeholder', 'supplier@example.com')}
            />
            <InputField
                name="phone"
                label={ft('phone', 'Phone')}
                placeholder={ft('phone_placeholder', '+1 (555) 123-4567')}
            />
            <AsyncSelectField
                name="branch_id"
                label={ft('branch', 'Branch')}
                url="/api/branches"
                placeholder={ft('branch_placeholder', 'Select a branch')}
            />
            <AsyncSelectField
                name="category_id"
                label={ft('category', 'Category')}
                url="/api/supplier-categories"
                placeholder={ft('category_placeholder', 'Select a category')}
            />
            <SelectField
                name="status"
                label={ft('status', 'Status')}
                options={[
                    { value: 'active', label: ft('status_active', 'Active') },
                    {
                        value: 'inactive',
                        label: ft('status_inactive', 'Inactive'),
                    },
                ]}
                placeholder={ft('status_placeholder', 'Select status')}
            />
            <TextareaField
                name="address"
                label={ft('address', 'Address')}
                placeholder={ft(
                    'address_placeholder',
                    '123 Main St, City, Country',
                )}
            />
        </EntityForm>
    );
});
