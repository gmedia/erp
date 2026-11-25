'use client';

import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { zodResolver } from '@hookform/resolvers/zod';
import { format } from 'date-fns';
import { Pencil, Plus, Trash } from 'lucide-react';
import { memo, useMemo } from 'react';
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

import { useTranslation } from '@/contexts/i18n-context';
import { JournalEntry } from '@/types/journal-entry';
import { toDateInputValue } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { JournalEntryFormData, journalEntryFormSchema } from '@/utils/schemas';
import { JournalEntryLineFormDialog } from './JournalEntryLineFormDialog';

type JournalEntryLineItem = NonNullable<JournalEntryFormData['lines']>[number];

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
            entry_date: toDateInputValue(new Date()),
            reference: '',
            description: '',
            lines: [],
        };
    }

    return {
        entry_date: toDateInputValue(journalEntry.entry_date),
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
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.journal_entries.${key}`);
            return translated === `form_fields.journal_entries.${key}`
                ? fallback
                : translated;
        };

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

        const lines = useWatch({
            control: form.control,
            name: 'lines',
        });

        const lineDialog = useEntityFormItemDialog({
            items: lines as JournalEntryLineItem[] | undefined,
            appendItem: append,
            updateItem: update,
        });

        useResetFormOnDefaultValues(form, defaultValues, { enabled: open });

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
                entry_date: format(data.entry_date, 'yyyy-MM-dd'),
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
                title={
                    isEdit
                        ? fl('edit_title', 'Edit Journal Entry')
                        : fl('add_title', 'Add New Journal Entry')
                }
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
                            label={{
                                key: 'form_fields.journal_entries.date',
                                fallback: 'Date',
                            }}
                            placeholder={{
                                key: 'form_fields.journal_entries.date_placeholder',
                                fallback: 'Pick a date',
                            }}
                        />
                        <InputField
                            name="reference"
                            label={{
                                key: 'form_fields.journal_entries.reference',
                                fallback: 'Reference',
                            }}
                            placeholder={{
                                key: 'form_fields.journal_entries.reference_placeholder',
                                fallback: 'Ref No.',
                            }}
                        />
                        <div className="md:col-span-3">
                            <InputField
                                name="description"
                                label={{
                                    key: 'form_fields.journal_entries.description',
                                    fallback: 'Description',
                                }}
                                placeholder={{
                                    key: 'form_fields.journal_entries.description_placeholder',
                                    fallback: 'Enter description',
                                }}
                            />
                        </div>
                    </div>

                    {/* Lines Section */}
                    <div className="space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <h3 className="text-lg font-medium">
                                {fl('lines_title', 'Journal Lines')}
                            </h3>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={lineDialog.handleCreateNewItem}
                                className="w-full sm:w-auto"
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                {fl('add_line', 'Add Line')}
                            </Button>
                        </div>
                        <div className="min-w-0 rounded-md border">
                            <Table className="min-w-[700px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[30%]">
                                            {fl('column_account', 'Account')}
                                        </TableHead>
                                        <TableHead className="w-[20%] text-right">
                                            {fl('column_debit', 'Debit')}
                                        </TableHead>
                                        <TableHead className="w-[20%] text-right">
                                            {fl('column_credit', 'Credit')}
                                        </TableHead>
                                        <TableHead className="w-[20%]">
                                            {fl('column_memo', 'Memo')}
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
                                                {fl(
                                                    'no_lines',
                                                    'No lines added yet.',
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        fields.map((field, index) => {
                                            const currentLine =
                                                lines?.[index] || field;
                                            return (
                                                <TableRow key={field.id}>
                                                    <TableCell>
                                                        {currentLine.account_code
                                                            ? `${currentLine.account_code} - `
                                                            : ''}
                                                        {currentLine.account_name ||
                                                            fl(
                                                                'selected_account',
                                                                'Selected Account',
                                                            )}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrencyByRegionalSettings(
                                                            Number(
                                                                currentLine.debit,
                                                            ) || 0,
                                                            {
                                                                locale: 'id-ID',
                                                                currency: 'IDR',
                                                            },
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrencyByRegionalSettings(
                                                            Number(
                                                                currentLine.credit,
                                                            ) || 0,
                                                            {
                                                                locale: 'id-ID',
                                                                currency: 'IDR',
                                                            },
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {currentLine.memo ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        <div className="flex justify-end gap-2">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    lineDialog.handleEditItem(
                                                                        index,
                                                                    )
                                                                }
                                                            >
                                                                <Pencil className="h-4 w-4 text-muted-foreground" />
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    remove(
                                                                        index,
                                                                    )
                                                                }
                                                                disabled={
                                                                    fields.length <=
                                                                        2 &&
                                                                    fields.length >
                                                                        0
                                                                }
                                                            >
                                                                <Trash className="h-4 w-4 text-destructive-foreground" />
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
                                        <TableCell className="font-bold">
                                            {fl('total', 'Total')}
                                        </TableCell>
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
                                                        ? 'font-bold text-destructive-foreground'
                                                        : 'font-bold text-success'
                                                }
                                            >
                                                {fl('difference', 'Diff')}:{' '}
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
                                <p className="p-4 text-sm text-destructive-foreground">
                                    {form.formState.errors.root.message}
                                </p>
                            )}
                            {form.formState.errors.lines && (
                                <p className="p-4 text-sm text-destructive-foreground">
                                    {JSON.stringify(
                                        form.formState.errors.lines,
                                    )}
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                <JournalEntryLineFormDialog
                    open={lineDialog.isItemDialogOpen}
                    onOpenChange={lineDialog.handleItemDialogOpenChange}
                    item={lineDialog.item}
                    onSave={lineDialog.handleSaveItem}
                />
            </EntityForm>
        );
    },
);
