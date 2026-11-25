'use client';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import { useTranslation } from '@/contexts/i18n-context';
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
    const { t } = useTranslation();
    const tf = (key: string, fallback: string) => {
        const translated = t(`form_fields.units.${key}`);
        return translated === `form_fields.units.${key}`
            ? fallback
            : translated;
    };
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
            title={
                entity
                    ? tf('edit_title', 'Edit Unit')
                    : tf('add_title', 'Add New Unit')
            }
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label={{ key: 'form_fields.units.name', fallback: 'Name' }}
                placeholder={{
                    key: 'form_fields.units.name_placeholder',
                    fallback: 'e.g., Kilogram',
                }}
            />
            <InputField
                name="symbol"
                label={{ key: 'form_fields.units.symbol', fallback: 'Symbol' }}
                placeholder={{
                    key: 'form_fields.units.symbol_placeholder',
                    fallback: 'e.g., kg',
                }}
            />
        </EntityForm>
    );
}
