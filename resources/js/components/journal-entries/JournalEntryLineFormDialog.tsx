'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import { ItemFormDialogShell } from '@/components/common/ItemFormDialog';
import { JournalLineFormFields } from '@/components/common/JournalLineFormFields';
import { type JournalEntryFormData } from '@/utils/schemas';

const journalEntryLineSchema = z
    .object({
        account_id: z.string().min(1, { message: 'Account is required.' }),
        account_name: z.string().optional(),
        account_code: z.string().optional(),
        debit: z.coerce.number().min(0).optional().default(0),
        credit: z.coerce.number().min(0).optional().default(0),
        memo: z.string().optional(),
        branch_id: z.string().optional(),
        branch_name: z.string().optional(),
    })
    .refine(
        () => {
            // Can't have both debit and credit be non-zero at the same time usually,
            // but let's just make sure they are not both zero if you want,
            // actually standard entries allow one to be > 0.
            return true;
        },
        {
            message: 'Must specify Debit or Credit.',
            path: ['root'],
        },
    );

type JournalEntryLineFormData = NonNullable<
    JournalEntryFormData['lines']
>[number];

interface JournalEntryLineFormDialogProps {
    readonly open: boolean;
    readonly onOpenChange: (open: boolean) => void;
    readonly item: JournalEntryLineFormData | null;
    readonly onSave: (data: JournalEntryLineFormData) => void;
}

export function JournalEntryLineFormDialog({
    open,
    onOpenChange,
    item,
    onSave,
}: JournalEntryLineFormDialogProps) {
    const defaultValues = useMemo<JournalEntryLineFormData>(() => {
        if (!item) {
            return {
                account_id: '',
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
            account_id: item.account_id || '',
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
        JournalEntryLineFormData,
        unknown,
        JournalEntryLineFormData
    >({
        resolver: zodResolver(journalEntryLineSchema) as Resolver<
            JournalEntryLineFormData,
            unknown,
            JournalEntryLineFormData
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
            itemDescription="journal entry line"
            titleLabel="Line"
            actionLabel="Line"
        >
            <JournalLineFormFields
                form={form}
                open={open}
                defaultValues={defaultValues}
            />
        </ItemFormDialogShell>
    );
}
