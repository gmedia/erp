'use client';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import { useEntityForm } from '@/hooks/useEntityForm';
import { unitFormSchema, type UnitFormData } from '@/utils/schemas';

interface UnitFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: { name: string; symbol?: string | null } | null;
    onSubmit: (data: UnitFormData) => void;
    isLoading?: boolean;
}

type UnitEntity = { name: string; symbol?: string | null };

const getDefaults = (entity?: UnitEntity | null): UnitFormData => ({
    name: entity?.name || '',
    symbol: entity?.symbol || '',
});

/**
 * UnitForm – a custom form for units with name and symbol.
 */
export function UnitForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}: Readonly<UnitFormProps>) {
    const form = useEntityForm<UnitFormData, UnitEntity>({
        schema: unitFormSchema,
        getDefaults,
        entity,
    });

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={entity ? 'Edit Unit' : 'Add New Unit'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField name="name" label="Name" placeholder="e.g., Kilogram" />
            <InputField name="symbol" label="Symbol" placeholder="e.g., kg" />
        </EntityForm>
    );
}
