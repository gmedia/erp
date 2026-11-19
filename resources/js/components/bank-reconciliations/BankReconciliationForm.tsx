'use client';

import { memo } from 'react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { useEntityForm } from '@/hooks/useEntityForm';
import { type BankReconciliation } from '@/types/bank-reconciliation';
import { toDateInputValue } from '@/utils/date-format';
import * as z from 'zod';

const bankReconciliationFormSchema = z
    .object({
        account_id: z.string().min(1, { message: 'Account is required.' }),
        fiscal_year_id: z
            .string()
            .min(1, { message: 'Fiscal year is required.' }),
        reconciliation_date: z
            .string()
            .min(1, { message: 'Reconciliation date is required.' }),
        period_start: z
            .string()
            .min(1, { message: 'Period start is required.' }),
        period_end: z.string().min(1, { message: 'Period end is required.' }),
        statement_balance: z.coerce.number({
            message: 'Statement balance is required.',
        }),
        book_balance: z.coerce.number(),
        branch_id: z.string().optional(),
    })
    .refine((data) => data.period_end >= data.period_start, {
        message: 'Period end must be after or equal to period start.',
        path: ['period_end'],
    });

type BankReconciliationFormData = z.infer<typeof bankReconciliationFormSchema>;

interface BankReconciliationFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: BankReconciliation | null;
    onSubmit: (data: BankReconciliationFormData) => void;
    isLoading?: boolean;
}

const getBankReconciliationFormDefaults = (
    entity?: BankReconciliation | null,
): BankReconciliationFormData => {
    if (!entity) {
        return {
            account_id: '',
            fiscal_year_id: '',
            reconciliation_date: toDateInputValue(new Date()),
            period_start: toDateInputValue(new Date()),
            period_end: toDateInputValue(new Date()),
            statement_balance: 0,
            book_balance: 0,
            branch_id: '',
        };
    }
    return {
        account_id: String(entity.account_id ?? entity.account?.id ?? ''),
        fiscal_year_id: String(
            entity.fiscal_year_id ?? entity.fiscal_year?.id ?? '',
        ),
        reconciliation_date: toDateInputValue(entity.reconciliation_date),
        period_start: toDateInputValue(entity.period_start),
        period_end: toDateInputValue(entity.period_end),
        statement_balance: Number(entity.statement_balance),
        book_balance: Number(entity.book_balance),
        branch_id: entity.branch_id
            ? String(entity.branch_id)
            : entity.branch?.id
              ? String(entity.branch.id)
              : '',
    };
};

export const BankReconciliationForm = memo<BankReconciliationFormProps>(
    function BankReconciliationForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const form = useEntityForm<
            BankReconciliationFormData,
            BankReconciliation
        >({
            schema: bankReconciliationFormSchema,
            getDefaults: getBankReconciliationFormDefaults,
            entity,
        });

        const accountCode = entity?.account?.code;
        const accountName = entity?.account?.name;
        const fiscalYearName = entity?.fiscal_year?.name;
        const branchName = entity?.branch?.name;

        return (
            <EntityForm
                form={form}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    entity
                        ? 'Edit Bank Reconciliation'
                        : 'Add New Bank Reconciliation'
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
            >
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <AsyncSelectField<{
                        code: string;
                        name: string;
                    }>
                        name="account_id"
                        label="Bank Account"
                        url="/api/accounts?is_active=1&has_children=0"
                        placeholder="Select Bank Account"
                        labelFn={(acc) => `${acc.code} - ${acc.name}`}
                        initialLabel={
                            accountCode && accountName
                                ? `${accountCode} - ${accountName}`
                                : ''
                        }
                    />
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
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <DatePickerField name="period_start" label="Period Start" />
                    <DatePickerField name="period_end" label="Period End" />
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <InputField
                        name="statement_balance"
                        label="Statement Balance"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                    />
                    <AsyncSelectField<{ name: string }>
                        name="branch_id"
                        label="Branch"
                        url="/api/branches"
                        placeholder="Select branch"
                        initialLabel={branchName || ''}
                    />
                </div>
            </EntityForm>
        );
    },
);
