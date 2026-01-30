'use client';

import * as React from 'react';
import { useEffect } from 'react';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import { InputField } from '@/components/common/InputField';
import { unitFormSchema, type UnitFormData } from '@/utils/schemas';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';

interface UnitFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    unit?: { name: string; symbol?: string | null } | null;
    // entity prop is also passed by EntityCrudPage factory for multi-word compatibility
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
    unit,
    entity,
    onSubmit,
    isLoading = false,
}: UnitFormProps) {
    const activeEntity = unit || entity;

    const form = useForm<UnitFormData>({
        resolver: zodResolver(unitFormSchema),
        defaultValues: {
            name: activeEntity?.name || '',
            symbol: activeEntity?.symbol || '',
        },
    });

    // Reset form when unit changes (for edit mode)
    useEffect(() => {
        if (open) {
            form.reset({
                name: activeEntity?.name || '',
                symbol: activeEntity?.symbol || '',
            });
        }
    }, [form, activeEntity, open]);

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={activeEntity ? 'Edit Unit' : 'Add New Unit'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label="Name"
                placeholder="e.g., Kilogram"
            />

            <InputField
                name="symbol"
                label="Symbol"
                placeholder="e.g., kg"
            />
        </EntityForm>
    );
}
