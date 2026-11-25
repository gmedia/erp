'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { useTranslation } from '@/contexts/i18n-context';
import { useEntityForm } from '@/hooks/useEntityForm';
import { type PeriodClosing } from '@/types/period-closing';
import * as z from 'zod';

const periodClosingFormSchema = z
    .object({
        fiscal_year_id: z.string().min(1, {
            message: 'validation.period_closing.fiscal_year_required',
        }),
        period_month: z.coerce.number(),
        period_year: z.coerce.number().min(2000).max(2100, {
            message: 'validation.period_closing.period_year_range',
        }),
        closing_type: z.enum(['monthly', 'annual'], {
            message: 'validation.period_closing.closing_type_required',
        }),
        retained_earnings_account_id: z.string(),
    })
    .superRefine((data, ctx) => {
        if (data.closing_type === 'monthly') {
            if (data.period_month < 1 || data.period_month > 12) {
                ctx.addIssue({
                    code: 'custom',
                    path: ['period_month'],
                    message: 'validation.period_closing.period_month_range',
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
                message:
                    'validation.period_closing.retained_earnings_account_required',
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
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.period_closings.${key}`);
            return translated === `form_fields.period_closings.${key}`
                ? fallback
                : translated;
        };

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
                    entity
                        ? fl('edit_title', 'Edit Period Closing')
                        : fl('add_title', 'Add New Period Closing')
                }
                onSubmit={handleSubmit}
                isLoading={isLoading}
            >
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <AsyncSelectField<{
                        name: string;
                    }>
                        name="fiscal_year_id"
                        label={fl('fiscal_year', 'Fiscal Year')}
                        url="/api/fiscal-years?status=open"
                        placeholder={fl(
                            'fiscal_year_placeholder',
                            'Select Fiscal Year',
                        )}
                        labelFn={(fy) => fy.name}
                        initialLabel={fiscalYearName || ''}
                        preferredMetaKey="preferred_fiscal_year_id"
                    />
                    <SelectField
                        name="closing_type"
                        label={fl('closing_type', 'Closing Type')}
                        options={[
                            {
                                value: 'monthly',
                                label: fl('closing_type_monthly', 'Monthly'),
                            },
                            {
                                value: 'annual',
                                label: fl('closing_type_annual', 'Annual'),
                            },
                        ]}
                    />
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {closingType !== 'annual' && (
                        <InputField
                            name="period_month"
                            label={fl('period_month', 'Period Month')}
                            type="number"
                            min={1}
                            max={12}
                            placeholder={fl('period_month_placeholder', '1-12')}
                        />
                    )}
                    <InputField
                        name="period_year"
                        label={fl('period_year', 'Period Year')}
                        type="number"
                        min={2000}
                        max={2100}
                        placeholder={fl('period_year_placeholder', 'YYYY')}
                    />
                </div>

                <AsyncSelectField<{
                    code: string;
                    name: string;
                }>
                    name="retained_earnings_account_id"
                    label={
                        closingType === 'annual'
                            ? fl(
                                  'retained_earnings_account_required',
                                  'Retained Earnings Account *',
                              )
                            : fl(
                                  'retained_earnings_account',
                                  'Retained Earnings Account',
                              )
                    }
                    url="/api/accounts?is_active=1&has_children=0"
                    placeholder={fl(
                        'retained_earnings_account_placeholder',
                        'Select Retained Earnings Account',
                    )}
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
