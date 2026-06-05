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
import EntityForm from '@/components/common/EntityForm';import { InputField } from '@/components/common/InputField';
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
import { type Budget } from '@/types/budget';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { budgetFormSchema, type BudgetFormData } from '@/utils/schemas';

const budgetTypeOptions = [
    { value: 'operational', label: 'Operational' },
    { value: 'capital', label: 'Capital' },
    { value: 'project', label: 'Project' },
    { value: 'revenue', label: 'Revenue' },
];

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
    const isEdit = !!entity;

    const defaultValues = useMemo(() => getBudgetFormDefaults(entity), [entity]);

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
        () => lines?.reduce((sum, l) => sum + (Number(l.allocated_amount) || 0), 0) ?? 0,
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
            title={isEdit ? 'Edit Budget' : 'Add New Budget'}
            onSubmit={onSubmit}
            isLoading={isLoading}
            className="sm:max-w-4xl"
        >
            <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                <div className="space-y-6 py-2">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <InputField
                            name="name"
                            label="Name"
                            placeholder="Budget name"
                        />
                        <SelectField
                            name="budget_type"
                            label="Budget Type"
                            options={budgetTypeOptions}
                        />
                    </div>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <AsyncSelectField
                            name="fiscal_year_id"
                            label="Fiscal Year"
                            url="/api/fiscal-years"
                            placeholder="Select fiscal year"
                            preferredMetaKey="preferred_fiscal_year_id"
                        />
                        <InputField
                            name="description"
                            label="Description"
                            placeholder="Optional description"
                        />
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <h3 className="text-lg font-semibold">Budget Lines</h3>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={handleAddLine}
                            >
                                <Plus className="mr-2 h-4 w-4" />
                                Add Line
                            </Button>
                        </div>

                        <div className="min-w-0 overflow-x-auto rounded-md border">
                            <Table className="min-w-[800px]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[25%]">Account</TableHead>
                                        <TableHead className="w-[18%]">Period Start</TableHead>
                                        <TableHead className="w-[18%]">Period End</TableHead>
                                        <TableHead className="w-[20%] text-right">Allocated Amount</TableHead>
                                        <TableHead className="w-[15%]">Notes</TableHead>
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
                                                No lines added yet.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        fields.map((field, index) => {
                                            const current = lines?.[index] ?? field;
                                            const isRowEditing = editingIndex === index;

                                            if (isRowEditing) {
                                                return (
                                                    <TableRow key={field.id}>
                                                        <TableCell>
                                                            <AsyncSelectField
                                                                name={`lines.${index}.account_id`}
                                                                label=""
                                                                url="/api/accounts"
                                                                placeholder="Select account"
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <InputField
                                                                name={`lines.${index}.period_start`}
                                                                label=""
                                                                type="date"
                                                                placeholder="YYYY-MM-DD"
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <InputField
                                                                name={`lines.${index}.period_end`}
                                                                label=""
                                                                type="date"
                                                                placeholder="YYYY-MM-DD"
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <InputField
                                                                name={`lines.${index}.allocated_amount`}
                                                                label=""
                                                                type="number"
                                                                placeholder="0"
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <InputField
                                                                name={`lines.${index}.notes`}
                                                                label=""
                                                                placeholder="Optional notes"
                                                            />
                                                        </TableCell>
                                                        <TableCell>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => setEditingIndex(null)}
                                                            >
                                                                <Pencil className="h-4 w-4 text-green-500" />
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
                                                        {current.account_name || 'Selected Account'}
                                                    </TableCell>
                                                    <TableCell>{current.period_start || '-'}</TableCell>
                                                    <TableCell>{current.period_end || '-'}</TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrencyByRegionalSettings(
                                                            Number(current.allocated_amount) || 0,
                                                            { locale: 'id-ID', currency: 'IDR' },
                                                        )}
                                                    </TableCell>
                                                    <TableCell>{current.notes || '-'}</TableCell>
                                                    <TableCell>
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => setEditingIndex(index)}
                                                            >
                                                                <Pencil className="h-4 w-4 text-muted-foreground" />
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="icon"
                                                                onClick={() => remove(index)}
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
                                        <TableCell colSpan={3} className="font-bold">
                                            Total
                                        </TableCell>
                                        <TableCell className="text-right font-bold">
                                            {formatCurrencyByRegionalSettings(totalAllocated, {
                                                locale: 'id-ID',
                                                currency: 'IDR',
                                            })}
                                        </TableCell>
                                        <TableCell colSpan={2} />
                                    </TableRow>
                                </TableFooter>
                            </Table>
                            {form.formState.errors.lines && (
                                <p className="p-4 text-sm text-red-500">
                                    {typeof form.formState.errors.lines === 'object' &&
                                    'message' in form.formState.errors.lines
                                        ? (form.formState.errors.lines as { message?: string }).message
                                        : 'Please fix the errors in the lines.'}
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </EntityForm>
    );
});
