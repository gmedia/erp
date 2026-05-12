'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { useEntityForm } from '@/hooks/useEntityForm';
import { type PeriodClosing } from '@/types/period-closing';
import * as z from 'zod';

const periodClosingFormSchema = z.object({
    fiscal_year_id: z.string().min(1, { message: 'Fiscal year is required.' }),
    period_month: z.coerce.number().min(1).max(12, { message: 'Period month must be between 1 and 12.' }),
    period_year: z.coerce.number().min(2000).max(2100, { message: 'Period year must be between 2000 and 2100.' }),
    closing_type: z.enum(['monthly', 'yearly'], { message: 'Closing type is required.' }),
    retained_earnings_account_id: z.string().min(1, { message: 'Retained earnings account is required.' }),
});

type PeriodClosingFormData = z.infer<typeof periodClosingFormSchema>;

interface PeriodClosingFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: PeriodClosing | null;
    onSubmit: (data: PeriodClosingFormData) => void;
    isLoading?: boolean;
}

const getPeriodClosingFormDefaults = (entity?: PeriodClosing | null): PeriodClosingFormData => {
    const now = new Date();
    if (!entity) {
        return { fiscal_year_id: '', period_month: now.getMonth() + 1, period_year: now.getFullYear(), closing_type: 'monthly', retained_earnings_account_id: '' };
    }
    return {
        fiscal_year_id: String(entity.fiscal_year_id),
        period_month: entity.period_month,
        period_year: entity.period_year,
        closing_type: entity.closing_type,
        retained_earnings_account_id: String(entity.retained_earnings_account_id),
    };
};

export const PeriodClosingForm = memo<PeriodClosingFormProps>(
    function PeriodClosingForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const form = useEntityForm<PeriodClosingFormData, PeriodClosing>({
            schema: periodClosingFormSchema,
            getDefaults: getPeriodClosingFormDefaults,
            entity,
        });

        const fiscalYearName = entity?.fiscal_year?.name;
        const retainedEarningsAccountCode =
            entity?.retained_earnings_account?.code;
        const retainedEarningsAccountName =
            entity?.retained_earnings_account?.name;

        return (
            <EntityForm
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    entity ? 'Edit Period Closing' : 'Add New Period Closing'
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
            >
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <AsyncSelectField<{
                        name: string;
                    }>
                        name="fiscal_year_id"
                        label="Fiscal Year"
                        url="/api/fiscal-years?status=open"
                        placeholder="Select Fiscal Year"
                        labelFn={(fy) => fy.name}
                        initialLabel={fiscalYearName || ''}
                    />
                    <SelectField
                        name="closing_type"
                        label="Closing Type"
                        options={[
                            { value: 'monthly', label: 'Monthly' },
                            { value: 'yearly', label: 'Yearly' },
                        ]}
                    />
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <InputField
                        name="period_month"
                        label="Period Month"
                        type="number"
                        min={1}
                        max={12}
                        placeholder="1-12"
                    />
                    <InputField
                        name="period_year"
                        label="Period Year"
                        type="number"
                        min={2000}
                        max={2100}
                        placeholder="YYYY"
                    />
                </div>

                <AsyncSelectField<{
                    code: string;
                    name: string;
                }>
                    name="retained_earnings_account_id"
                    label="Retained Earnings Account"
                    url="/api/accounts?is_active=1&has_children=0"
                    placeholder="Select Retained Earnings Account"
                    labelFn={(acc) => `${acc.code} - ${acc.name}`}
                    initialLabel={
                        retainedEarningsAccountCode &&
                        retainedEarningsAccountName
                            ? `${retainedEarningsAccountCode} - ${retainedEarningsAccountName}`
                            : ''
                    }
                />
            </EntityForm>
        );
    },
);
