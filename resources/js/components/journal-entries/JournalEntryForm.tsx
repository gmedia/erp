'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { memo, useEffect, useMemo } from 'react';
import { useFieldArray, useForm, useWatch } from 'react-hook-form';
import { format } from 'date-fns';
import { Plus, Trash } from 'lucide-react';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import { InputField } from '@/components/common/InputField';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableFooter,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';

import { JournalEntry, JournalEntryFormData as JEM } from '@/types/journal-entry';
import { journalEntryFormSchema, JournalEntryFormData } from '@/utils/schemas';

interface JournalEntryFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    journalEntry?: JournalEntry | null;
    onSubmit: (data: JournalEntryFormData) => void;
    isLoading?: boolean;
}

const getJournalEntryFormDefaults = (
    journalEntry?: JournalEntry | null,
): JournalEntryFormData => {
    if (!journalEntry) {
        return {
            entry_date: new Date(),
            reference: '',
            description: '',
            lines: [
                { account_id: '', debit: 0, credit: 0, memo: '' },
                { account_id: '', debit: 0, credit: 0, memo: '' },
            ],
        };
    }

    return {
        entry_date: new Date(journalEntry.entry_date),
        reference: journalEntry.reference || '',
        description: journalEntry.description,
        lines: journalEntry.lines.map((line) => ({
            account_id: String(line.account_id),
            debit: Number(line.debit),
            credit: Number(line.credit),
            memo: line.memo || '',
        })),
    };
};

export const JournalEntryForm = memo<JournalEntryFormProps>(function JournalEntryForm({
    open,
    onOpenChange,
    journalEntry,
    onSubmit,
    isLoading = false,
}) {
    const defaultValues = useMemo(
        () => getJournalEntryFormDefaults(journalEntry),
        [journalEntry],
    );

    const form = useForm<JournalEntryFormData>({
        resolver: zodResolver(journalEntryFormSchema) as any,
        defaultValues,
    });

    const { fields, append, remove } = useFieldArray({
        control: form.control,
        name: 'lines',
    });

    const lines = useWatch({
        control: form.control,
        name: 'lines',
    });

    const totalDebit = lines?.reduce((acc, line) => acc + (Number(line.debit) || 0), 0) || 0;
    const totalCredit = lines?.reduce((acc, line) => acc + (Number(line.credit) || 0), 0) || 0;
    const difference = totalDebit - totalCredit;

    useEffect(() => {
        if (open) {
             form.reset(defaultValues);
        }
    }, [open, defaultValues, form]);

    const handleFormSubmit = (data: JournalEntryFormData) => {
        onSubmit({
            ...data,
            entry_date: format(data.entry_date, 'yyyy-MM-dd') as any, // Format for API
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {journalEntry ? 'Edit Journal Entry' : 'Add New Journal Entry'}
                    </DialogTitle>
                </DialogHeader>

                <Form {...form}>
                    <form onSubmit={form.handleSubmit(handleFormSubmit)} className="space-y-6">
                        {/* Header Section */}
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <DatePickerField 
                                name="entry_date" 
                                label="Date" 
                                placeholder="Pick a date"
                            />
                            <InputField 
                                name="reference" 
                                label="Reference" 
                                placeholder="Ref No."
                            />
                             <div className="md:col-span-3">
                                <InputField 
                                    name="description" 
                                    label="Description" 
                                    placeholder="Enter description"
                                />
                            </div>
                        </div>

                        {/* Lines Section */}
                        <div className="border rounded-md">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[30%]">Account</TableHead>
                                        <TableHead className="w-[20%]">Debit</TableHead>
                                        <TableHead className="w-[20%]">Credit</TableHead>
                                        <TableHead className="w-[25%]">Memo</TableHead>
                                        <TableHead className="w-[5%]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {fields.map((field, index) => (
                                        <TableRow key={field.id}>
                                            <TableCell>
                                                <AsyncSelectField
                                                    name={`lines.${index}.account_id`}
                                                    label=""
                                                    url="/api/accounts?is_active=1&has_children=0" // Assuming API filter
                                                    placeholder="Select Account"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <InputField
                                                    name={`lines.${index}.debit`}
                                                    label=""
                                                    type="number"
                                                    step="0.01"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                 <InputField
                                                    name={`lines.${index}.credit`}
                                                    label=""
                                                    type="number"
                                                    step="0.01"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <InputField
                                                    name={`lines.${index}.memo`}
                                                    label=""
                                                    placeholder="Memo"
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => remove(index)}
                                                    disabled={fields.length <= 2}
                                                >
                                                    <Trash className="h-4 w-4 text-red-500" />
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                                <TableFooter>
                                    <TableRow>
                                        <TableCell>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => append({ account_id: '', debit: 0, credit: 0, memo: '' })}
                                            >
                                                <Plus className="mr-2 h-4 w-4" /> Add Line
                                            </Button>
                                        </TableCell>
                                        <TableCell className="font-bold text-right">
                                            {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(totalDebit)}
                                        </TableCell>
                                        <TableCell className="font-bold text-right">
                                            {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(totalCredit)}
                                        </TableCell>
                                        <TableCell colSpan={2} className="text-right">
                                             <span className={Math.abs(difference) > 0.01 ? 'text-red-500 font-bold' : 'text-green-500 font-bold'}>
                                                Diff: {new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(difference)}
                                             </span>
                                        </TableCell>
                                    </TableRow>
                                </TableFooter>
                            </Table>
                            {form.formState.errors.root && (
                                <p className="text-red-500 text-sm p-4">{form.formState.errors.root.message}</p>
                            )}
                             {form.formState.errors.lines && (
                                <p className="text-red-500 text-sm p-4">{String(form.formState.errors.lines.message)}</p>
                            )}
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={isLoading}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={isLoading || Math.abs(difference) > 0.01}>
                                {isLoading ? 'Saving...' : 'Save'}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
});
