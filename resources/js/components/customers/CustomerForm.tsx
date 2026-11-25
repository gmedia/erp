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

import { Customer, CustomerFormData } from '@/types/entity';
import { customerFormSchema } from '@/utils/schemas';

interface CustomerFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Customer | null;
    onSubmit: (data: CustomerFormData) => void;
    isLoading?: boolean;
}

const ft = (key: string, fallback: string) => ({
    key: `form_fields.customers.${key}`,
    fallback,
});

const statusOptions = [
    { value: 'active', label: ft('status_active', 'Active') },
    { value: 'inactive', label: ft('status_inactive', 'Inactive') },
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
    const { t } = useTranslation();
    const tf = (key: string, fallback: string) => {
        const translated = t(`form_fields.customers.${key}`);
        return translated === `form_fields.customers.${key}`
            ? fallback
            : translated;
    };
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
            title={
                entity
                    ? tf('edit_title', 'Edit Customer')
                    : tf('add_title', 'Add New Customer')
            }
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label={ft('name', 'Name')}
                placeholder={ft('name_placeholder', 'Customer name')}
            />
            <PartyEmailPhoneFields
                fl={ft}
                emailPlaceholder="customer@example.com"
                phonePlaceholder="+62 812 3456 7890"
            />
            <TextareaField
                name="address"
                label={ft('address', 'Address')}
                placeholder={ft('address_placeholder', 'Full address')}
                rows={3}
            />
            <PartyBranchCategoryFields
                fl={ft}
                categoryUrl="/api/customer-categories"
                categoryPlaceholder="Select a category"
            />
            <SelectField
                name="status"
                label={ft('status', 'Status')}
                options={statusOptions}
                placeholder={ft('status_placeholder', 'Select status')}
            />
            <TextareaField
                name="notes"
                label={ft('notes', 'Notes')}
                placeholder={ft(
                    'notes_placeholder',
                    'Additional notes (optional)',
                )}
                rows={2}
            />
        </EntityForm>
    );
});
