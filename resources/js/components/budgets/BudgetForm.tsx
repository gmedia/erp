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

import AsyncSelectField from '@/components/common/AsyncSelectField';
import { DatePickerField } from '@/components/common/DatePickerField';
import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
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
import { type Budget } from '@/types/budget';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { budgetFormSchema, type BudgetFormData } from '@/utils/schemas';

const budgetTypeKeys = [
    { value: 'operational', key: 'type_operational', fallback: 'Operational' },
    { value: 'capital', key: 'type_capital', fallback: 'Capital' },
    { value: 'project', key: 'type_project', fallback: 'Project' },
    { value: 'revenue', key: 'type_revenue', fallback: 'Revenue' },
] as const;

interface BudgetFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: Budget | null;
    onSubmit: (data: BudgetFormData) => void;
    isLoading?: boolean;
}

const getBudgetFormDefaults = (entity?: Budget | null): BudgetFormData => {
    if (!entity) {
        return {
            name: '',
            description: null,
            fiscal_year_id: 0,
            budget_type: 'operational',
            lines: [],
        };
    }

    return {
        name: entity.name,
        description: entity.description ?? null,
        fiscal_year_id: entity.fiscal_year_id,
        budget_type: entity.budget_type as BudgetFormData['budget_type'],
        lines: (entity.lines ?? []).map((line) => ({
            id: line.id,
            account_id: line.account_id,
            account_name: line.account?.name ?? '',
            account_code: line.account?.code ?? '',
            period_start: line.period_start,
            period_end: line.period_end,
            allocated_amount: line.allocated_amount,
            notes: line.notes ?? null,
        })),
    };
};

export const BudgetForm = memo<BudgetFormProps>(function BudgetForm({
    open,
    onOpenChange,
    entity,
    onSubmit,
    isLoading = false,
}) {
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.budgets.${key}`);
        return translated === `form_fields.budgets.${key}`
            ? fallback
            : translated;
    };

    const isEdit = !!entity;

    const defaultValues = useMemo(
        () => getBudgetFormDefaults(entity),
        [entity],
    );

    const form = useForm<z.input<typeof budgetFormSchema>>({
        resolver: zodResolver(budgetFormSchema),
        defaultValues,
    });

    const { fields, append, remove } = useFieldArray({
        control: form.control,
        name: 'lines',
    });

    const lines = useWatch({ control: form.control, name: 'lines' });

    const [editingIndex, setEditingIndex] = useState<number | null>(null);

    useEffect(() => {
        if (open) {
            form.reset(defaultValues);
            setEditingIndex(null);
        }
    }, [open, defaultValues, form]);

    const totalAllocated = useMemo(
        () =>
            lines?.reduce(
                (sum, l) => sum + (Number(l.allocated_amount) || 0),
                0,
            ) ?? 0,
        [lines],
    );

    const handleAddLine = () => {
        append({
            account_id: 0,
            account_name: '',
            account_code: '',
            period_start: '',
            period_end: '',
            allocated_amount: 0,
            notes: null,
        });
        setEditingIndex(fields.length);
    };

    return (
        <EntityForm
            form={form as UseFormReturn<BudgetFormData>}
            open={open}
            onOpenChange={onOpenChange}
            title={
                isEdit
                    ? fl('edit_title', 'Edit Budget')
                    : fl('add_title', 'Add New Budget')
            }
            onSubmit={onSubmit}
            isLoading={isLoading}
            className="sm:max-w-4xl"
        >
            <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                <div className="space-y-6 py-2">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <InputField
                            name="name"
                            label={fl('name', 'Name')}
                            placeholder={fl('name_placeholder', 'Budget name')}
                        />
                        <SelectField
                            name="budget_type"
                            label={fl('budget_type', 'Budget Type')}
                            options={budgetTypeKeys.map((option) => ({
                                value: option.value,
                                label: fl(option.key, option.fallback),
                            }))}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <AsyncSelectField
                            name="fiscal_year_id"
                            label={fl('fiscal_year', 'Fiscal Year')}
                            url="/api/fiscal-years"
                            placeholder={fl(
                                'fiscal_year_placeholder',
                                'Select fiscal year',
                            )}
                            preferredMetaKey="preferred_fiscal_year_id"
                        />
                        <InputField
                            name="description"
                            label={fl('description', 'Description')}
                            placeholder={fl(
                                'description_placeholder',
                                'Optional description',
                            )}
                        />
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold">
                                {fl('lines_title', 'Budget Lines')}
                            </h3>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleAddLine}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                {fl('add_line', 'Add Line')}
                            </Button>
                        </div>

                        <div className="min-w-0 overflow-x-auto rounded-md border">
                            <Table className="min-w-[800px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[25%]">
                                            {fl('column_account', 'Account')}
                                        </TableHead>
                                        <TableHead className="w-[18%]">
                                            {fl(
                                                'column_period_start',
                                                'Period Start',
                                            )}
                                        </TableHead>
                                        <TableHead className="w-[18%]">
                                            {fl(
                                                'column_period_end',
                                                'Period End',
                                            )}
                                        </TableHead>
                                        <TableHead className="w-[20%] text-right">
                                            {fl(
                                                'column_allocated_amount',
                                                'Allocated Amount',
                                            )}
                                        </TableHead>
                                        <TableHead className="w-[15%]">
                                            {fl('column_notes', 'Notes')}
                                        </TableHead>
                                        <TableHead className="w-[4%]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {fields.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={6}
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
                                            const current =
                                                lines?.[index] ?? field;
                                            const isRowEditing =
                                                editingIndex === index;

                                            if (isRowEditing) {
                                                return (
                                                    <TableRow key={field.id}>
                                                        <TableCell>
                                                            <AsyncSelectField
                                                                name={`lines.${index}.account_id`}
                                                                label=""
                                                                url="/api/accounts"
                                                                placeholder={fl(
                                                                    'account_placeholder',
                                                                    'Select account',
                                                                )}
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <DatePickerField
                                                                name={`lines.${index}.period_start`}
                                                                label=""
                                                                placeholder={fl(
                                                                    'pick_start_date',
                                                                    'Pick start date',
                                                                )}
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <DatePickerField
                                                                name={`lines.${index}.period_end`}
                                                                label=""
                                                                placeholder={fl(
                                                                    'pick_end_date',
                                                                    'Pick end date',
                                                                )}
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <InputField
                                                                name={`lines.${index}.allocated_amount`}
                                                                label=""
                                                                type="number"
                                                                placeholder={fl(
                                                                    'amount_placeholder',
                                                                    '0',
                                                                )}
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <InputField
                                                                name={`lines.${index}.notes`}
                                                                label=""
                                                                placeholder={fl(
                                                                    'notes_placeholder',
                                                                    'Optional notes',
                                                                )}
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    setEditingIndex(
                                                                        null,
                                                                    )
                                                                }
                                                            >
                                                                <Pencil className="h-4 w-4 text-success" />
                                                            </Button>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            }

                                            return (
                                                <TableRow key={field.id}>
                                                    <TableCell>
                                                        {current.account_code
                                                            ? `${current.account_code} - `
                                                            : ''}
                                                        {current.account_name ||
                                                            fl(
                                                                'selected_account',
                                                                'Selected Account',
                                                            )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {current.period_start ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        {current.period_end ||
                                                            '-'}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrencyByRegionalSettings(
                                                            Number(
                                                                current.allocated_amount,
                                                            ) || 0,
                                                            {
                                                                locale: 'id-ID',
                                                                currency: 'IDR',
                                                            },
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {current.notes || '-'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() =>
                                                                    setEditingIndex(
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
                                        <TableCell
                                            colSpan={3}
                                            className="font-bold"
                                        >
                                            {fl('total', 'Total')}
                                        </TableCell>
                                        <TableCell className="text-right font-bold">
                                            {formatCurrencyByRegionalSettings(
                                                totalAllocated,
                                                {
                                                    locale: 'id-ID',
                                                    currency: 'IDR',
                                                },
                                            )}
                                        </TableCell>
                                        <TableCell colSpan={2} />
                                    </TableRow>
                                </TableFooter>
                            </Table>
                            {form.formState.errors.lines && (
                                <p className="p-4 text-sm text-destructive-foreground">
                                    {(() => {
                                        const linesError =
                                            form.formState.errors.lines;
                                        const message =
                                            typeof linesError === 'object' &&
                                            linesError !== null &&
                                            'message' in linesError
                                                ? String(linesError.message)
                                                : '';
                                        if (!message) {
                                            return fl(
                                                'lines_error_fallback',
                                                'Please fix the errors in the lines.',
                                            );
                                        }
                                        const translated = t(message);
                                        return translated === message
                                            ? message
                                            : translated;
                                    })()}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </EntityForm>
    );
});
