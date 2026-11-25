'use client';

import * as z from 'zod';

import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import SelectField from '@/components/common/SelectField';
import { useTranslation } from '@/contexts/i18n-context';
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
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.fiscal_years.${key}`);
        return translated === `form_fields.fiscal_years.${key}`
            ? fallback
            : translated;
    };

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
            title={
                entity
                    ? fl('edit_title', 'Edit Fiscal Year')
                    : fl('add_title', 'Add New Fiscal Year')
            }
            onSubmit={handleFormSubmit}
            isLoading={isLoading}
        >
            <NameField
                name="name"
                label={fl('name', 'Name')}
                placeholder={fl('name_placeholder', 'e.g., 2026')}
            />

            <div className="grid grid-cols-2 gap-4">
                <DatePickerField
                    name="start_date"
                    label={fl('start_date', 'Start Date')}
                />
                <DatePickerField
                    name="end_date"
                    label={fl('end_date', 'End Date')}
                />
            </div>

            <SelectField
                name="status"
                label={fl('status', 'Status')}
                placeholder={fl('status_placeholder', 'Select status')}
                options={[
                    { label: fl('status_open', 'Open'), value: 'open' },
                    { label: fl('status_closed', 'Closed'), value: 'closed' },
                    { label: fl('status_locked', 'Locked'), value: 'locked' },
                ]}
            />
        </EntityForm>
    );
}
