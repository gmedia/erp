'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { ItemFormDialogShell } from '@/components/common/ItemFormDialog';
import { JournalLineFormFields } from '@/components/common/JournalLineFormFields';

const recurringJournalLineSchema = z.object({
    account_id: z.coerce.number().min(1, { message: 'Account is required.' }),
    account_name: z.string().optional(),
    account_code: z.string().optional(),
    debit: z.coerce.number().min(0).optional().default(0),
    credit: z.coerce.number().min(0).optional().default(0),
    memo: z.string().optional(),
    branch_id: z.string().optional(),
    branch_name: z.string().optional(),
});

type RecurringJournalLineFormData = z.infer<typeof recurringJournalLineSchema>;

interface RecurringJournalLineFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: RecurringJournalLineFormData | null;
    readonly onSave: (data: RecurringJournalLineFormData) => void;
}

export function RecurringJournalLineFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: RecurringJournalLineFormDialogProps) {
    const defaultValues = useMemo<RecurringJournalLineFormData>(() => {
        if (!item) {
            return {
                account_id: 0,
                account_name: '',
                account_code: '',
                debit: 0,
                credit: 0,
                memo: '',
                branch_id: '',
                branch_name: '',
            };
        }

        return {
            account_id: item.account_id || 0,
            account_name: item.account_name || '',
            account_code: item.account_code || '',
            debit: Number(item.debit || 0),
            credit: Number(item.credit || 0),
            memo: item.memo || '',
            branch_id: item.branch_id || '',
            branch_name: item.branch_name || '',
        };
    }, [item]);

    const form = useForm<
        RecurringJournalLineFormData,
        unknown,
        RecurringJournalLineFormData
    >({
        resolver: zodResolver(recurringJournalLineSchema) as Resolver<
            RecurringJournalLineFormData,
            unknown,
            RecurringJournalLineFormData
        >,
        defaultValues,
    });

    useEffect(() => {
        if (open) {
            form.reset(defaultValues);
        }
    }, [open, defaultValues, form]);

    return (
        <ItemFormDialogShell
            open={open}
            onOpenChange={onOpenChange}
            item={item}
            form={form}
            onSave={onSave}
            itemDescription="recurring journal line"
        >
            <JournalLineFormFields
                form={form}
                open={open}
                defaultValues={defaultValues}
            />
        </ItemFormDialogShell>
    );
}
