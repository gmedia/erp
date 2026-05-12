'use client';

import { zodResolver } from '@hookform/resolvers/zod';
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
import SelectField from '@/components/common/SelectField';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

import {
    type RecurringJournal,
    type RecurringJournalLine,
} from '@/types/recurring-journal';
import { RecurringJournalLineFormDialog } from './RecurringJournalLineFormDialog';
import { RecurringJournalLinesTable } from './RecurringJournalLinesTable';

const recurringJournalLineSchema = z.object({
    account_id: z.coerce.number().min(1, { message: 'Account is required.' }),
    account_name: z.string().optional(),
    account_code: z.string().optional(),
    debit: z.coerce.number().min(0).default(0),
    credit: z.coerce.number().min(0).default(0),
    memo: z.string().optional(),
});

const recurringJournalFormSchema = z.object({
    name: z.string().min(1, { message: 'Name is required.' }),
    frequency: z.enum(['daily', 'weekly', 'monthly', 'quarterly', 'yearly'], {
        message: 'Frequency is required.',
    }),
    next_run_date: z.date({ message: 'Next run date is required.' }),
    auto_post: z.boolean().default(false),
    is_active: z.boolean().default(true),
    reference_template: z.string().optional(),
    description_template: z
        .string()
        .min(1, { message: 'Description template is required.' }),
    lines: z.array(recurringJournalLineSchema).min(1, {
        message: 'At least one line is required.',
    }),
});

type RecurringJournalFormData = z.infer<typeof recurringJournalFormSchema>;

interface RecurringJournalFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: RecurringJournal | null;
    onSubmit: (data: RecurringJournalFormData) => void;
    isLoading?: boolean;
}

const getRecurringJournalFormDefaults = (
    entity?: RecurringJournal | null,
): RecurringJournalFormData => {
    if (!entity) {
        return {
            name: '',
            frequency: 'monthly',
            next_run_date: new Date(),
            auto_post: false,
            is_active: true,
            reference_template: '',
            description_template: '',
            lines: [],
        };
    }

    return {
        name: entity.name || '',
        frequency: entity.frequency || 'monthly',
        next_run_date: new Date(entity.next_run_date),
        auto_post: entity.auto_post || false,
        is_active: entity.is_active ?? true,
        reference_template: entity.reference_template || '',
        description_template: entity.description_template || '',
        lines: entity.lines.map((line) => ({
            account_id: Number(line.account_id),
            account_name: line.account_name || '',
            account_code: line.account_code || '',
            debit: Number(line.debit) || 0,
            credit: Number(line.credit) || 0,
            memo: line.memo || '',
        })),
    };
};

export const RecurringJournalForm = memo<RecurringJournalFormProps>(
    function RecurringJournalForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const isEdit = !!entity;

        const defaultValues = useMemo(
            () => getRecurringJournalFormDefaults(entity),
            [entity],
        );

        const form = useForm<z.input<typeof recurringJournalFormSchema>>({
            resolver: zodResolver(recurringJournalFormSchema),
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

        const handleSaveLine = (data: RecurringJournalLine) => {
            if (editingIndex !== null) {
                update(editingIndex, data);
            } else {
                append(data);
            }
            setIsItemDialogOpen(false);
            setEditingIndex(null);
        };

        const handleRemoveLine = (index: number) => {
            remove(index);
        };

        const totalDebit = useMemo(() => {
            return (
                lines?.reduce(
                    (sum, line) => sum + (Number(line.debit) || 0),
                    0,
                ) || 0
            );
        }, [lines]);

        const totalCredit = useMemo(() => {
            return (
                lines?.reduce(
                    (sum, line) => sum + (Number(line.credit) || 0),
                    0,
                ) || 0
            );
        }, [lines]);

        const isBalanced = totalDebit === totalCredit;

        return (
            <>
                <EntityForm
                    form={form as UseFormReturn<RecurringJournalFormData>}
                    open={open}
                    onOpenChange={onOpenChange}
                    title={
                        isEdit
                            ? 'Edit Recurring Journal'
                            : 'Add New Recurring Journal'
                    }
                    onSubmit={onSubmit}
                    isLoading={isLoading}
                >
                    <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                        <div className="space-y-6 py-2">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <InputField
                                    name="name"
                                    label="Name"
                                    placeholder="Recurring Journal Name"
                                />
                                <SelectField
                                    name="frequency"
                                    label="Frequency"
                                    options={[
                                        { value: 'daily', label: 'Daily' },
                                        { value: 'weekly', label: 'Weekly' },
                                        { value: 'monthly', label: 'Monthly' },
                                        {
                                            value: 'quarterly',
                                            label: 'Quarterly',
                                        },
                                        { value: 'yearly', label: 'Yearly' },
                                    ]}
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <DatePickerField
                                    name="next_run_date"
                                    label="Next Run Date"
                                />
                                <div className="flex flex-col gap-4">
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="auto_post"
                                            checked={form.watch('auto_post')}
                                            onCheckedChange={(checked) =>
                                                form.setValue(
                                                    'auto_post',
                                                    !!checked,
                                                )
                                            }
                                        />
                                        <Label htmlFor="auto_post">
                                            Auto Post
                                        </Label>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <Checkbox
                                            id="is_active"
                                            checked={form.watch('is_active')}
                                            onCheckedChange={(checked) =>
                                                form.setValue(
                                                    'is_active',
                                                    !!checked,
                                                )
                                            }
                                        />
                                        <Label htmlFor="is_active">
                                            Is Active
                                        </Label>
                                    </div>
                                </div>
                            </div>

                            <InputField
                                name="reference_template"
                                label="Reference Template"
                                placeholder="e.g., RJ-{YYYY}-{MM}-{DD}"
                            />

                            <InputField
                                name="description_template"
                                label="Description Template"
                                placeholder="Recurring journal description"
                            />

                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-semibold">
                                        Lines
                                    </h3>
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

                                {!isBalanced && lines && lines.length > 0 && (
                                    <div className="rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">
                                        Warning: Total Debit and Total Credit
                                        must be equal.
                                    </div>
                                )}

                                <RecurringJournalLinesTable
                                    lines={
                                        fields.map((_, index) => ({
                                            id: fields[index]?.id ? Number(fields[index].id) : index,
                                            account_code: lines?.[index]?.account_code,
                                            account_name: lines?.[index]?.account_name,
                                            debit: Number(lines?.[index]?.debit || 0),
                                            credit: Number(lines?.[index]?.credit || 0),
                                            memo: lines?.[index]?.memo,
                                        })) || []
                                    }
                                    totalDebit={totalDebit}
                                    totalCredit={totalCredit}
                                    emptyMessage='No lines added yet. Click "Add Line" to start.'
                                    actions={(index) => (
                                        <div className="flex items-center gap-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    handleEditLine(index)
                                                }
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                onClick={() =>
                                                    handleRemoveLine(index)
                                                }
                                            >
                                                <Trash className="h-4 w-4 text-red-500" />
                                            </Button>
                                        </div>
                                    )}
                                />
                            </div>
                        </div>
                    </div>
                </EntityForm>

                <RecurringJournalLineFormDialog
                    open={isItemDialogOpen}
                    onOpenChange={setIsItemDialogOpen}
                    item={
                        editingIndex !== null && lines?.[editingIndex]
                            ? {
                                  account_id: Number(
                                      lines[editingIndex].account_id,
                                  ),
                                  account_name:
                                      lines[editingIndex].account_name,
                                  account_code:
                                      lines[editingIndex].account_code,
                                  debit: Number(lines[editingIndex].debit),
                                  credit: Number(lines[editingIndex].credit),
                                  memo: lines[editingIndex].memo,
                              }
                            : null
                    }
                    onSave={handleSaveLine}
                />
            </>
        );
    },
);
