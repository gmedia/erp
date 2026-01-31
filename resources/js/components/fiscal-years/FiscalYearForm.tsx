'use client';

import * as React from 'react';
import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import { DatePickerField } from '@/components/common/DatePickerField';
import SelectField from '@/components/common/SelectField';
import { fiscalYearFormSchema, type FiscalYearFormData } from '@/utils/schemas';
import { type FiscalYear } from '@/types/entity';

interface FiscalYearFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    fiscalYear?: FiscalYear | null;
    entity?: FiscalYear | null;
    onSubmit: (data: FiscalYearFormData) => void;
    isLoading?: boolean;
}

export function FiscalYearForm({
    open,
    onOpenChange,
    fiscalYear,
    entity,
    onSubmit,
    isLoading = false,
}: FiscalYearFormProps) {
    const activeEntity = fiscalYear || entity;

    const form = useForm<FiscalYearFormData>({
        resolver: zodResolver(fiscalYearFormSchema),
        defaultValues: {
            name: activeEntity?.name || '',
            start_date: activeEntity?.start_date ? new Date(activeEntity.start_date) : undefined as any,
            end_date: activeEntity?.end_date ? new Date(activeEntity.end_date) : undefined as any,
            status: activeEntity?.status || 'open',
        },
    });

    useEffect(() => {
        if (open) {
            form.reset({
                name: activeEntity?.name || '',
                start_date: activeEntity?.start_date ? new Date(activeEntity.start_date) : undefined as any,
                end_date: activeEntity?.end_date ? new Date(activeEntity.end_date) : undefined as any,
                status: activeEntity?.status || 'open',
            });
        }
    }, [form, activeEntity, open]);

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={activeEntity ? 'Edit Fiscal Year' : 'Add New Fiscal Year'}
            onSubmit={onSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label="Name"
                placeholder="e.g., 2026"
            />

            <div className="grid grid-cols-2 gap-4">
                <DatePickerField
                    name="start_date"
                    label="Start Date"
                />
                <DatePickerField
                    name="end_date"
                    label="End Date"
                />
            </div>

            <SelectField
                name="status"
                label="Status"
                placeholder="Select status"
                options={[
                    { label: 'Open', value: 'open' },
                    { label: 'Closed', value: 'closed' },
                    { label: 'Locked', value: 'locked' },
                ]}
            />
        </EntityForm>
    );
}
