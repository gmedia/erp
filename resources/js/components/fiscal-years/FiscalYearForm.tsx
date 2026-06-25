'use client';

import * as z from 'zod';

import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { useEntityForm } from '@/hooks/useEntityForm';
import { type FiscalYear } from '@/types/entity';
import { fiscalYearFormSchema, type FiscalYearFormData } from '@/utils/schemas';

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
    start_date: entity?.start_date || '',
    end_date: entity?.end_date || '',
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
        onSubmit(data as FiscalYearFormData);
    };

    return (
        <EntityForm<FiscalYearFormData>
            form={form}
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
