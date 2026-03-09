'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect } from 'react';
import { useForm, type UseFormReturn } from 'react-hook-form';
import * as z from 'zod';

import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { type FiscalYear } from '@/types/entity';
import { fiscalYearFormSchema, type FiscalYearFormData } from '@/utils/schemas';
import { format } from 'date-fns';

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

    const form = useForm<z.input<typeof fiscalYearFormSchema>>({
        resolver: zodResolver(fiscalYearFormSchema),
        defaultValues: {
            name: activeEntity?.name || '',
            start_date: activeEntity?.start_date
                ? new Date(activeEntity.start_date)
                : (undefined as unknown as Date),
            end_date: activeEntity?.end_date
                ? new Date(activeEntity.end_date)
                : (undefined as unknown as Date),
            status: activeEntity?.status || 'open',
        },
    });

    useEffect(() => {
        if (open) {
            form.reset({
                name: activeEntity?.name || '',
                start_date: activeEntity?.start_date
                    ? new Date(activeEntity.start_date)
                    : (undefined as unknown as Date),
                end_date: activeEntity?.end_date
                    ? new Date(activeEntity.end_date)
                    : (undefined as unknown as Date),
                status: activeEntity?.status || 'open',
            });
        }
    }, [form, activeEntity, open]);

    const handleFormSubmit = (data: z.input<typeof fiscalYearFormSchema>) => {
        const payload = {
            ...data,
            start_date: format(data.start_date, 'yyyy-MM-dd') as unknown as Date,
            end_date: format(data.end_date, 'yyyy-MM-dd') as unknown as Date,
        } as FiscalYearFormData;
        onSubmit(payload);
    };

    return (
        <EntityForm<FiscalYearFormData>
            form={
                form as unknown as UseFormReturn<
                    FiscalYearFormData,
                    unknown,
                    FiscalYearFormData
                >
            }
            open={open}
            onOpenChange={onOpenChange}
            title={activeEntity ? 'Edit Fiscal Year' : 'Add New Fiscal Year'}
            onSubmit={handleFormSubmit}
            isLoading={isLoading}
        >
            <NameField name="name" label="Name" placeholder="e.g., 2026" />

            <div className="grid grid-cols-2 gap-4">
                <DatePickerField name="start_date" label="Start Date" />
                <DatePickerField name="end_date" label="End Date" />
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
