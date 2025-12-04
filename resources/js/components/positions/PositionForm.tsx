'use client';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import { FormMessage } from '@/components/ui/form';
import { Position, PositionFormData } from '@/types/position';
import { zodResolver } from '@hookform/resolvers/zod';
import * as React from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

const formSchema = z.object({
    name: z
        .string()
        .min(2, { message: 'Name must be at least 2 characters.' })
        .max(255, { message: 'Maximum 255 characters.' }),
});

interface PositionFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    position?: Position | null;
    onSubmit: (data: PositionFormData) => void;
    isLoading?: boolean;
}

export function PositionForm({
    open,
    onOpenChange,
    position,
    onSubmit,
    isLoading = false,
}: PositionFormProps) {
    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: position ? { name: position.name } : undefined,
    });

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={position ? 'Edit Position' : 'Add New Position'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField name="name" label="Name" placeholder="e.g., Manager">
                <FormMessage />
            </NameField>
        </EntityForm>
    );
}