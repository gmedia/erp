'use client';

import { memo } from 'react';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import {
    PartyBranchCategoryFields,
    PartyEmailPhoneFields,
} from '@/components/common/PartyContactFields';
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
            <PartyEmailPhoneFields
                fl={ft}
                emailPlaceholder="supplier@example.com"
                phonePlaceholder="+1 (555) 123-4567"
            />
            <PartyBranchCategoryFields
                fl={ft}
                categoryUrl="/api/supplier-categories"
                categoryPlaceholder="Select a category"
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
