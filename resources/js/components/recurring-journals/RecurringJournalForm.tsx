'use client';

import { useEntityFormItemDialog } from '@/hooks/useEntityFormItemDialog';
import { useResetFormOnDefaultValues } from '@/hooks/useResetFormOnDefaultValues';
import { zodResolver } from '@hookform/resolvers/zod';
import { Pencil, Plus, Trash2 } from 'lucide-react';
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
import SelectField from '@/components/common/SelectField';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/contexts/i18n-context';

import {
    type RecurringJournal,
    type RecurringJournalLine,
} from '@/types/recurring-journal';
import { toDateInputValue } from '@/utils/date-format';
import { RecurringJournalLineFormDialog } from './RecurringJournalLineFormDialog';
import { RecurringJournalLinesTable } from './RecurringJournalLinesTable';

const recurringJournalLineSchema = z.object({
    account_id: z.coerce.number().min(1, {
        message: 'validation.recurring_journal.account_required',
    }),
    account_name: z.string().optional(),
    account_code: z.string().optional(),
    debit: z.coerce.number().min(0).default(0),
    credit: z.coerce.number().min(0).default(0),
    memo: z.string().optional(),
});

const recurringJournalFormSchema = z.object({
    name: z.string().min(1, {
        message: 'validation.recurring_journal.name_required',
    }),
    frequency: z.enum(['daily', 'weekly', 'monthly', 'quarterly', 'annual'], {
        message: 'validation.recurring_journal.frequency_required',
    }),
    next_run_date: z.string().min(1, {
        message: 'validation.recurring_journal.next_run_date_required',
    }),
    auto_post: z.boolean().default(false),
    is_active: z.boolean().default(true),
    reference_template: z.string().optional(),
    description_template: z.string().min(1, {
        message: 'validation.recurring_journal.description_template_required',
    }),
    lines: z.array(recurringJournalLineSchema).min(1, {
        message: 'validation.recurring_journal.lines_min_one',
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
            next_run_date: toDateInputValue(new Date()),
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
        next_run_date: toDateInputValue(entity.next_run_date),
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

const LineActions = memo<{
    index: number;
    editTitle: string;
    removeTitle: string;
    onEditLine: (index: number) => void;
    onRemoveLine: (index: number) => void;
}>(function LineActions({
    index,
    editTitle,
    removeTitle,
    onEditLine,
    onRemoveLine,
}) {
    return (
        <>
            <Button
                variant="ghost"
                size="icon"
                onClick={() => onEditLine(index)}
                title={editTitle}
            >
                <Pencil className="h-4 w-4" />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                onClick={() => onRemoveLine(index)}
                title={removeTitle}
            >
                <Trash2 className="h-4 w-4" />
            </Button>
        </>
    );
});

export const RecurringJournalForm = memo<RecurringJournalFormProps>(
    function RecurringJournalForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const { t } = useTranslation();
        const fl = (key: string, fallback: string) => {
            const translated = t(`form_fields.recurring_journals.${key}`);
            return translated === `form_fields.recurring_journals.${key}`
                ? fallback
                : translated;
        };

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

        const lines = useWatch({
            control: form.control,
            name: 'lines',
        });

        const lineDialog = useEntityFormItemDialog({
            items: lines as unknown as RecurringJournalLine[] | undefined,
            appendItem: (data: RecurringJournalLine) => append(data),
            updateItem: (index: number, data: RecurringJournalLine) =>
                update(index, data),
            mapEditingItem: (line) =>
                line
                    ? {
                          account_id: Number(line.account_id),
                          account_name: line.account_name,
                          account_code: line.account_code,
                          debit: Number(line.debit),
                          credit: Number(line.credit),
                          memo: line.memo,
                      }
                    : null,
        });

        useResetFormOnDefaultValues(form, defaultValues, { enabled: open });

        const handleRemoveLine = (index: number) => remove(index);

        const totalDebit = useMemo(
            () =>
                lines?.reduce(
                    (sum, line) => sum + (Number(line.debit) || 0),
                    0,
                ) || 0,
            [lines],
        );

        const totalCredit = useMemo(
            () =>
                lines?.reduce(
                    (sum, line) => sum + (Number(line.credit) || 0),
                    0,
                ) || 0,
            [lines],
        );

        const isBalanced = totalDebit === totalCredit;

        const renderLineActions = (index: number) => (
            <LineActions
                index={index}
                editTitle={fl('edit_line', 'Edit line')}
                removeTitle={fl('remove_line', 'Remove line')}
                onEditLine={lineDialog.handleEditItem}
                onRemoveLine={handleRemoveLine}
            />
        );

        return (
            <>
                <EntityForm
                    form={form as UseFormReturn<RecurringJournalFormData>}
                    open={open}
                    onOpenChange={onOpenChange}
                    title={
                        isEdit
                            ? fl('edit_title', 'Edit Recurring Journal')
                            : fl('add_title', 'Add New Recurring Journal')
                    }
                    onSubmit={onSubmit}
                    isLoading={isLoading}
                >
                    <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                        <div className="space-y-6 py-2">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <InputField
                                    name="name"
                                    label={fl('name', 'Name')}
                                    placeholder={fl(
                                        'name_placeholder',
                                        'Recurring Journal Name',
                                    )}
                                />
                                <SelectField
                                    name="frequency"
                                    label={fl('frequency', 'Frequency')}
                                    options={[
                                        {
                                            value: 'daily',
                                            label: fl(
                                                'frequency_daily',
                                                'Daily',
                                            ),
                                        },
                                        {
                                            value: 'weekly',
                                            label: fl(
                                                'frequency_weekly',
                                                'Weekly',
                                            ),
                                        },
                                        {
                                            value: 'monthly',
                                            label: fl(
                                                'frequency_monthly',
                                                'Monthly',
                                            ),
                                        },
                                        {
                                            value: 'quarterly',
                                            label: fl(
                                                'frequency_quarterly',
                                                'Quarterly',
                                            ),
                                        },
                                        {
                                            value: 'annual',
                                            label: fl(
                                                'frequency_annual',
                                                'Yearly',
                                            ),
                                        },
                                    ]}
                                />
                            </div>

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <DatePickerField
                                    name="next_run_date"
                                    label={fl('next_run_date', 'Next Run Date')}
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
                                            {fl('auto_post', 'Auto Post')}
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
                                            {fl('is_active', 'Is Active')}
                                        </Label>
                                    </div>
                                </div>
                            </div>

                            <InputField
                                name="reference_template"
                                label={fl(
                                    'reference_template',
                                    'Reference Template',
                                )}
                                placeholder={fl(
                                    'reference_template_placeholder',
                                    'e.g., RJ-{YYYY}-{MM}-{DD}',
                                )}
                            />

                            <InputField
                                name="description_template"
                                label={fl(
                                    'description_template',
                                    'Description Template',
                                )}
                                placeholder={fl(
                                    'description_template_placeholder',
                                    'Recurring journal description',
                                )}
                            />

                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-lg font-semibold">
                                        {fl('lines_title', 'Lines')}
                                    </h3>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={lineDialog.handleCreateNewItem}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        {fl('add_line', 'Add Line')}
                                    </Button>
                                </div>

                                {!isBalanced && lines && lines.length > 0 && (
                                    <div className="rounded-md bg-warning-soft p-3 text-sm text-warning">
                                        {fl(
                                            'unbalanced_warning',
                                            'Warning: Total Debit and Total Credit must be equal.',
                                        )}
                                    </div>
                                )}

                                <RecurringJournalLinesTable
                                    lines={fields.map((_, index) => ({
                                        id: index,
                                        account_code:
                                            lines?.[index]?.account_code,
                                        account_name:
                                            lines?.[index]?.account_name,
                                        debit: Number(
                                            lines?.[index]?.debit || 0,
                                        ),
                                        credit: Number(
                                            lines?.[index]?.credit || 0,
                                        ),
                                        memo: lines?.[index]?.memo,
                                    }))}
                                    totalDebit={totalDebit}
                                    totalCredit={totalCredit}
                                    emptyMessage={fl(
                                        'no_lines',
                                        'No lines added yet. Click "Add Line" to start.',
                                    )}
                                    actions={renderLineActions}
                                />
                            </div>
                        </div>
                    </div>
                </EntityForm>

                <RecurringJournalLineFormDialog
                    open={lineDialog.isItemDialogOpen}
                    onOpenChange={lineDialog.handleItemDialogOpenChange}
                    item={lineDialog.item}
                    onSave={lineDialog.handleSaveItem}
                />
            </>
        );
    },
);
