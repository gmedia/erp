'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useMemo } from 'react';
import { type Resolver, useForm } from 'react-hook-form';
import { z } from 'zod';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { InputField } from '@/components/common/InputField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';

const recurringJournalLineSchema = z.object({
    account_id: z.coerce.number().min(1, { message: 'Account is required.' }),
    account_name: z.string().optional(),
    account_code: z.string().optional(),
    debit: z.coerce.number().min(0).optional().default(0),
    credit: z.coerce.number().min(0).optional().default(0),
    memo: z.string().optional(),
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
            };
        }

        return {
            account_id: item.account_id || 0,
            account_name: item.account_name || '',
            account_code: item.account_code || '',
            debit: Number(item.debit || 0),
            credit: Number(item.credit || 0),
            memo: item.memo || '',
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
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{item ? 'Edit Line' : 'Add Line'}</DialogTitle>
                    <DialogDescription className="sr-only">
                        {item
                            ? 'Edit recurring journal line.'
                            : 'Add recurring journal line.'}
                    </DialogDescription>
                </DialogHeader>

                <Form {...form}>
                    <form
                        onSubmit={(event) => {
                            event.stopPropagation();
                            form.handleSubmit(onSave)(event);
                        }}
                        className="space-y-4"
                    >
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-1">
                            <AsyncSelectField<{
                                code: string;
                                name: string;
                                normal_balance: string;
                            }>
                                key={`account-${defaultValues.account_id || 'new'}-${open ? 'open' : 'closed'}`}
                                name="account_id"
                                label="Account"
                                url="/api/accounts?is_active=1&has_children=0"
                                placeholder="Select Account"
                                labelFn={(acc) =>
                                    `${acc.code} - ${acc.name} (${acc.normal_balance})`
                                }
                                initialLabel={
                                    defaultValues.account_code
                                        ? `${defaultValues.account_code} - ${defaultValues.account_name}`
                                        : ''
                                }
                                onItemSelect={(account) => {
                                    form.setValue(
                                        'account_name',
                                        account?.name || '',
                                        { shouldDirty: true },
                                    );
                                    form.setValue(
                                        'account_code',
                                        account?.code || '',
                                        { shouldDirty: true },
                                    );
                                }}
                            />
                        </div>

                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <InputField
                                name="debit"
                                label="Debit"
                                type="number"
                                min={0}
                                step="0.01"
                                placeholder="0"
                            />
                            <InputField
                                name="credit"
                                label="Credit"
                                type="number"
                                min={0}
                                step="0.01"
                                placeholder="0"
                            />
                        </div>

                        <InputField
                            name="memo"
                            label="Memo"
                            placeholder="Line Memo"
                        />

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit">
                                {item ? 'Update Line' : 'Save Line'}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
