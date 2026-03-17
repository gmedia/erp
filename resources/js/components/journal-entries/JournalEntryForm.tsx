'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { Pencil, Plus, Trash } from 'lucide-react';
import { memo, useEffect, useMemo, useState } from 'react';
import {
    useFieldArray,
    useForm,
    useWatch,
    type UseFormReturn,
} from 'react-hook-form';
import * as z from 'zod';

import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableFooter,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { JournalEntry } from '@/types/journal-entry';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { JournalEntryFormData, journalEntryFormSchema } from '@/utils/schemas';
import { JournalEntryLineFormDialog } from './JournalEntryLineFormDialog';

interface JournalEntryFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    // We accept these props to handle passed data from EntityCrudPage
    journalEntry?: JournalEntry | null;
    item?: JournalEntry | null;
    entity?: JournalEntry | null;
    onSubmit: (data: JournalEntryFormData) => void;
    isLoading?: boolean;
}

const getJournalEntryFormDefaults = (
    journalEntry?: JournalEntry | null,
): JournalEntryFormData => {
    if (!journalEntry) {
        return {
            entry_date: new Date() as any, // Cast to any to satisfy type temporarily or use proper Date type handling if needed
            reference: '',
            description: '',
            lines: [],
        };
    }

    return {
        entry_date: new Date(journalEntry.entry_date) as any,
        reference: journalEntry.reference || '',
        description: journalEntry.description || '',
        lines: journalEntry.lines.map((line) => ({
            account_id: String(line.account_id),
            account_name: line.account_name || '',
            account_code: line.account_code || '',
            debit: Number(line.debit) || 0,
            credit: Number(line.credit) || 0,
            memo: line.memo || '',
        })),
    };
};

export const JournalEntryForm = memo<JournalEntryFormProps>(
    function JournalEntryForm({
        open,
        onOpenChange,
        journalEntry,
        item,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        // Consolidate the input data source
        const data = journalEntry || item || entity;
        const isEdit = !!data;

        const defaultValues = useMemo(
            () => getJournalEntryFormDefaults(data),
            [data],
        );

        const form = useForm<z.input<typeof journalEntryFormSchema>>({
            resolver: zodResolver(journalEntryFormSchema),
            defaultValues,
        });

        const { fields, append, update, remove } = useFieldArray({
            control: form.control,
            name: 'lines',
        });

        const [isItemDialogOpen, setIsItemDialogOpen] = useState(false);
        const [editingIndex, setEditingIndex] = useState<number | null>(null);

        const lines = useWatch({
            control: form.control,
            name: 'lines',
        });

        // Reset form when dialog opens/closes or data changes
        useEffect(() => {
            if (open) {
                form.reset(defaultValues);
            }
        }, [open, defaultValues, form]);

        const handleCreateNewLine = () => {
            setEditingIndex(null);
            setIsItemDialogOpen(true);
        };

        const handleEditLine = (index: number) => {
            setEditingIndex(index);
            setIsItemDialogOpen(true);
        };

        const totalDebit =
            lines?.reduce((acc, line) => acc + (Number(line.debit) || 0), 0) ||
            0;
        const totalCredit =
            lines?.reduce((acc, line) => acc + (Number(line.credit) || 0), 0) ||
            0;
        const difference = totalDebit - totalCredit;

        const handleFormSubmit = (
            data: z.input<typeof journalEntryFormSchema>,
        ) => {
            const payload = {
                ...data,
                entry_date: format(
                    data.entry_date,
                    'yyyy-MM-dd',
                ) as unknown as Date, // Format for API, cast to satisfy schema if needed
            } as JournalEntryFormData;
            onSubmit(payload);
        };

        return (
            <EntityForm<JournalEntryFormData>
                form={
                    form as unknown as UseFormReturn<
                        JournalEntryFormData,
                        unknown,
                        JournalEntryFormData
                    >
                }
                open={open}
                onOpenChange={onOpenChange}
                title={isEdit ? 'Edit Journal Entry' : 'Add New Journal Entry'}
                onSubmit={handleFormSubmit}
                isLoading={isLoading}
                submitDisabled={Math.abs(difference) > 0.01}
                className="sm:max-w-4xl"
            >
                <div className="space-y-6">
                    {/* Header Section */}
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
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
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-medium">Journal Lines</h3>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleCreateNewLine}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Add Line
                            </Button>
                        </div>
                        <div className="rounded-md border overflow-hidden max-w-[calc(100vw-3.5rem)] sm:max-w-none">
                            <Table className="min-w-[700px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[30%]">
                                            Account
                                        </TableHead>
                                        <TableHead className="w-[20%] text-right">
                                            Debit
                                        </TableHead>
                                        <TableHead className="w-[20%] text-right">
                                            Credit
                                        </TableHead>
                                        <TableHead className="w-[20%]">
                                            Memo
                                        </TableHead>
                                        <TableHead className="w-[10%] text-right"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {fields.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={5}
                                                className="h-24 text-center text-muted-foreground"
                                            >
                                                No lines added yet.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        fields.map((field, index) => {
                                            const currentLine = lines?.[index] || field;
                                            return (
                                                <TableRow key={field.id}>
                                                    <TableCell>
                                                        {currentLine.account_code ? `${currentLine.account_code} - ` : ''}
                                                        {currentLine.account_name || 'Selected Account'}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrencyByRegionalSettings(
                                                            Number(currentLine.debit) || 0,
                                                            { locale: 'id-ID', currency: 'IDR' }
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrencyByRegionalSettings(
                                                            Number(currentLine.credit) || 0,
                                                            { locale: 'id-ID', currency: 'IDR' }
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {currentLine.memo || '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex justify-end gap-2">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => handleEditLine(index)}
                                                            >
                                                                <Pencil className="h-4 w-4 text-muted-foreground" />
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => remove(index)}
                                                                disabled={fields.length <= 2 && fields.length > 0}
                                                            >
                                                                <Trash className="h-4 w-4 text-red-500" />
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    )}
                                </TableBody>
                                <TableFooter>
                                    <TableRow>
                                        <TableCell className="font-bold">Total</TableCell>
                                        <TableCell className="text-right font-bold">
                                            {formatCurrencyByRegionalSettings(
                                                totalDebit,
                                                {
                                                    locale: 'id-ID',
                                                    currency: 'IDR',
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right font-bold">
                                            {formatCurrencyByRegionalSettings(
                                                totalCredit,
                                                {
                                                    locale: 'id-ID',
                                                    currency: 'IDR',
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell
                                            colSpan={2}
                                            className="text-right"
                                        >
                                            <span
                                                className={
                                                    Math.abs(difference) > 0.01
                                                        ? 'font-bold text-red-500'
                                                        : 'font-bold text-green-500'
                                                }
                                            >
                                                Diff:{' '}
                                                {formatCurrencyByRegionalSettings(
                                                    difference,
                                                    {
                                                        locale: 'id-ID',
                                                        currency: 'IDR',
                                                    },
                                                )}
                                            </span>
                                        </TableCell>
                                    </TableRow>
                                </TableFooter>
                            </Table>
                            {form.formState.errors.root && (
                                <p className="p-4 text-sm text-red-500">
                                    {form.formState.errors.root.message}
                                </p>
                            )}
                            {form.formState.errors.lines && (
                                <p className="p-4 text-sm text-red-500">
                                    {JSON.stringify(form.formState.errors.lines)}
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                <JournalEntryLineFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={(nextOpen) => {
                        setIsItemDialogOpen(nextOpen);
                        if (!nextOpen) {
                            setEditingIndex(null);
                        }
                    }}
                    item={
                        editingIndex === null
                            ? null
                            : (lines?.[editingIndex] as NonNullable<JournalEntryFormData['lines']>[number] ?? null)
                    }
                    onSave={(data) => {
                        if (editingIndex === null) {
                            append(data);
                        } else {
                            update(editingIndex, data);
                        }
                        setIsItemDialogOpen(false);
                    }}
                />
            </EntityForm>
        );
    },
);
