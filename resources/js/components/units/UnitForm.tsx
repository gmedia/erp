'use client';

import { useEffect } from 'react';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import NameField from '@/components/common/NameField';
import { unitFormSchema, type UnitFormData } from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';

interface UnitFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: { name: string; symbol?: string | null } | null;
    onSubmit: (data: UnitFormData) => void;
    isLoading?: boolean;
}

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
    const form = useForm<UnitFormData>({
        resolver: zodResolver(unitFormSchema),
        defaultValues: {
            name: entity?.name || '',
            symbol: entity?.symbol || '',
        },
    });

    // Reset form when entity changes (for edit mode)
    useEffect(() => {
        if (open) {
            form.reset({
                name: entity?.name || '',
                symbol: entity?.symbol || '',
            });
        }
    }, [form, entity, open]);

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
