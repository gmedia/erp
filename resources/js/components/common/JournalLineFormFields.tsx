'use client';

import type { FieldValues, UseFormReturn } from 'react-hook-form';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { InputField } from '@/components/common/InputField';

interface JournalLineFormFieldsProps<TFormValues extends FieldValues> {
    readonly form: UseFormReturn<TFormValues>;
    readonly open: boolean;
    readonly defaultValues: TFormValues;
    readonly accountUrl?: string;
    readonly branchUrl?: string;
    readonly showDepartment?: boolean;
    readonly departmentUrl?: string;
}

export function JournalLineFormFields<TFormValues extends FieldValues>({
    form,
    open,
    defaultValues,
    accountUrl = '/api/accounts?is_active=1&postable=1',
    branchUrl = '/api/branches',
    showDepartment = false,
    departmentUrl = '/api/departments',
}: Readonly<JournalLineFormFieldsProps<TFormValues>>) {
    const accountId = (defaultValues as Record<string, unknown>).account_id;
    const accountKey =
        (typeof accountId === 'string' || typeof accountId === 'number') &&
        accountId
            ? String(accountId)
            : 'new';
    const keyBase = `${accountKey}-${open ? 'open' : 'closed'}`;

    return (
        <>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-1">
                <AsyncSelectField<{
                    code: string;
                    name: string;
                    normal_balance: string;
                }>
                    key={`account-${keyBase}`}
                    name={'account_id' as never}
                    label="Account"
                    url={accountUrl}
                    placeholder="Select Account"
                    labelFn={(acc) =>
                        `${acc.code} - ${acc.name} (${acc.normal_balance})`
                    }
                    initialLabel={
                        (defaultValues as Record<string, unknown>).account_code
                            ? `${(defaultValues as Record<string, unknown>).account_code} - ${(defaultValues as Record<string, unknown>).account_name}`
                            : ''
                    }
                    onItemSelect={(account) => {
                        form.setValue(
                            'account_name' as never,
                            (account?.name || '') as never,
                            { shouldDirty: true },
                        );
                        form.setValue(
                            'account_code' as never,
                            (account?.code || '') as never,
                            { shouldDirty: true },
                        );
                    }}
                />
                <AsyncSelectField<{ name: string }>
                    key={`branch-${keyBase}`}
                    name={'branch_id' as never}
                    label="Branch"
                    url={branchUrl}
                    placeholder="Select branch"
                    initialLabel={
                        ((defaultValues as Record<string, unknown>)
                            .branch_name as string) || ''
                    }
                    onItemSelect={(branch) => {
                        form.setValue(
                            'branch_name' as never,
                            (branch?.name || '') as never,
                            { shouldDirty: true },
                        );
                    }}
                />
                {showDepartment ? (
                    <AsyncSelectField<{ name: string }>
                        key={`department-${keyBase}`}
                        name={'department_id' as never}
                        label="Department"
                        url={departmentUrl}
                        placeholder="Select department (optional)"
                        initialLabel={
                            ((defaultValues as Record<string, unknown>)
                                .department_name as string) || ''
                        }
                        onItemSelect={(department) => {
                            form.setValue(
                                'department_name' as never,
                                (department?.name || '') as never,
                                { shouldDirty: true },
                            );
                        }}
                    />
                ) : null}
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                <InputField
                    name={'debit' as never}
                    label="Debit"
                    type="number"
                    min={0}
                    step="0.01"
                    placeholder="0"
                />
                <InputField
                    name={'credit' as never}
                    label="Credit"
                    type="number"
                    min={0}
                    step="0.01"
                    placeholder="0"
                />
            </div>

            <InputField
                name={'memo' as never}
                label="Memo"
                placeholder="Line Memo"
            />
        </>
    );
}
