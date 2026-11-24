'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { useEntityForm } from '@/hooks/useEntityForm';
import { type PeriodClosing } from '@/types/period-closing';
import * as z from 'zod';

const periodClosingFormSchema = z
    .object({
        fiscal_year_id: z
            .string()
            .min(1, { message: 'Fiscal year is required.' }),
        period_month: z.coerce.number(),
        period_year: z.coerce.number().min(2000).max(2100, {
            message: 'Period year must be between 2000 and 2100.',
        }),
        closing_type: z.enum(['monthly', 'annual'], {
            message: 'Closing type is required.',
        }),
        retained_earnings_account_id: z.string(),
    })
    .superRefine((data, ctx) => {
        if (data.closing_type === 'monthly') {
            if (data.period_month < 1 || data.period_month > 12) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['period_month'],
                    message: 'Period month must be between 1 and 12.',
                });
            }
        }
        if (
            data.closing_type === 'annual' &&
            !data.retained_earnings_account_id
        ) {
            ctx.addIssue({
                code: 'custom',
                path: ['retained_earnings_account_id'],
                message: 'Retained earnings account is required.',
            });
        }
    });

type PeriodClosingFormData = z.infer<typeof periodClosingFormSchema>;

type PeriodClosingPayload = Omit<PeriodClosingFormData, 'period_month'> & {
    period_month: number | null;
};

interface PeriodClosingFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: PeriodClosing | null;
    onSubmit: (data: PeriodClosingPayload) => void;
    isLoading?: boolean;
}

const getPeriodClosingFormDefaults = (
    entity?: PeriodClosing | null,
): PeriodClosingFormData => {
    const now = new Date();
    if (!entity) {
        return {
            fiscal_year_id: '',
            period_month: now.getMonth() + 1,
            period_year: now.getFullYear(),
            closing_type: 'monthly',
            retained_earnings_account_id: '',
        };
    }
    return {
        fiscal_year_id: String(entity.fiscal_year_id),
        period_month: entity.period_month ?? 0,
        period_year: entity.period_year,
        closing_type: entity.closing_type,
        retained_earnings_account_id: entity.retained_earnings_account_id
            ? String(entity.retained_earnings_account_id)
            : '',
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

        const handleSubmit = (data: PeriodClosingFormData) => {
            // Annual tidak memakai bulan; backend menerima period_month kosong.
            onSubmit({
                ...data,
                period_month:
                    data.closing_type === 'annual' ? null : data.period_month,
            });
        };

        const closingType = form.watch('closing_type');
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
                onSubmit={handleSubmit}
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
                        preferredMetaKey="preferred_fiscal_year_id"
                    />
                    <SelectField
                        name="closing_type"
                        label="Closing Type"
                        options={[
                            { value: 'monthly', label: 'Monthly' },
                            { value: 'annual', label: 'Annual' },
                        ]}
                    />
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {closingType !== 'annual' && (
                        <InputField
                            name="period_month"
                            label="Period Month"
                            type="number"
                            min={1}
                            max={12}
                            placeholder="1-12"
                        />
                    )}
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
                    label={
                        closingType === 'annual'
                            ? 'Retained Earnings Account *'
                            : 'Retained Earnings Account'
                    }
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
