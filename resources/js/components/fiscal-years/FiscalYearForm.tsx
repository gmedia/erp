'use client';

import { type UseFormReturn } from 'react-hook-form';
import * as z from 'zod';

import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { useEntityForm } from '@/hooks/useEntityForm';
import { type FiscalYear } from '@/types/entity';
import { fiscalYearFormSchema, type FiscalYearFormData } from '@/utils/schemas';
import { format } from 'date-fns';

interface FiscalYearFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: FiscalYear | null;
    onSubmit: (data: FiscalYearFormData) => void;
    isLoading?: boolean;
}

type FiscalYearFormInput = z.input<typeof fiscalYearFormSchema>;

const getDefaults = (entity?: FiscalYear | null): FiscalYearFormInput => ({
    name: entity?.name || '',
    start_date: entity?.start_date
        ? new Date(entity.start_date)
        : (undefined as unknown as Date),
    end_date: entity?.end_date
        ? new Date(entity.end_date)
        : (undefined as unknown as Date),
    status: entity?.status || 'open',
});

export function FiscalYearForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}: Readonly<FiscalYearFormProps>) {
    const form = useEntityForm<FiscalYearFormInput, FiscalYear>({
        schema: fiscalYearFormSchema,
        getDefaults,
        entity,
    });

    const handleFormSubmit = (data: FiscalYearFormInput) => {
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
            title={entity ? 'Edit Fiscal Year' : 'Add New Fiscal Year'}
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
