'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityForm } from '@/hooks/useEntityForm';

import { type Warehouse, type WarehouseFormData } from '@/types/entity';
import { warehouseFormSchema } from '@/utils/schemas';

interface WarehouseFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Warehouse | null;
    onSubmit: (data: WarehouseFormData) => void;
    isLoading?: boolean;
}

const getWarehouseFormDefaults = (
    entity?: Warehouse | null,
): WarehouseFormData => {
    if (!entity) {
        return { code: '', name: '', branch_id: '' };
    }

    return {
        code: entity.code,
        name: entity.name,
        branch_id:
            typeof entity.branch === 'object' && entity.branch
                ? String(entity.branch.id)
                : String(entity.branch_id || ''),
    };
};

export const WarehouseForm = memo<WarehouseFormProps>(function WarehouseForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.warehouses.${key}`);
        return translated === `form_fields.warehouses.${key}`
            ? fallback
            : translated;
    };
    const form = useEntityForm<WarehouseFormData, Warehouse>({
        schema: warehouseFormSchema,
        getDefaults: getWarehouseFormDefaults,
        entity,
    });

    return (
        <EntityForm<WarehouseFormData>
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={
                entity
                    ? fl('edit_title', 'Edit Warehouse')
                    : fl('add_title', 'Add New Warehouse')
            }
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <InputField
                name="code"
                label={fl('code', 'Code')}
                placeholder={fl('code_placeholder', 'Enter warehouse code')}
            />
            <InputField
                name="name"
                label={fl('name', 'Name')}
                placeholder={fl('name_placeholder', 'Enter warehouse name')}
            />
            <AsyncSelectField
                name="branch_id"
                label={fl('branch', 'Branch')}
                url="/api/branches"
                placeholder={fl('branch_placeholder', 'Select a branch')}
                initialLabel={entity?.branch?.name}
            />
        </EntityForm>
    );
});
