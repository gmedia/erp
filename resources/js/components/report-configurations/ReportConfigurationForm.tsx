'use client';

import { zodResolver } from '@hookform/resolvers/zod';
import { Plus, Trash } from 'lucide-react';
import { memo, useEffect, useMemo } from 'react';
import { useFieldArray, useForm, type UseFormReturn } from 'react-hook-form';
import * as z from 'zod';

import EntityForm from '@/components/common/EntityForm';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { type ReportConfiguration } from '@/types/report-configuration';

const reportSectionSchema = z.object({
    id: z.number().optional(),
    code: z.string().min(1, { message: 'Section code is required.' }),
    name: z.string().min(1, { message: 'Section name is required.' }),
    sort_order: z.coerce.number().int().min(0).default(0),
    section_type: z.enum([
        'header',
        'detail',
        'subtotal',
        'total',
        'separator',
    ]),
    account_type_filter: z.string().optional().nullable(),
    account_sub_type_filter: z.string().optional().nullable(),
    sign_convention: z.enum(['normal', 'reversed']).default('normal'),
    formula: z.string().optional().nullable(),
    is_active: z.boolean().default(true),
    parent_code: z.string().optional().nullable(),
});

const reportConfigurationFormSchema = z.object({
    code: z.string().min(1, { message: 'Code is required.' }).max(255),
    name: z.string().min(1, { message: 'Name is required.' }).max(255),
    description: z.string().optional().nullable(),
    report_type: z.enum([
        'balance_sheet',
        'income_statement',
        'cash_flow',
        'trial_balance',
        'custom',
    ]),
    is_active: z.boolean().default(true),
    sections: z.array(reportSectionSchema).default([]),
});

type ReportConfigurationFormData = z.infer<
    typeof reportConfigurationFormSchema
>;

interface ReportConfigurationFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    entity?: ReportConfiguration | null;
    onSubmit: (data: ReportConfigurationFormData) => void;
    isLoading?: boolean;
}

const reportTypeOptions = [
    { value: 'balance_sheet', label: 'Balance Sheet' },
    { value: 'income_statement', label: 'Income Statement' },
    { value: 'cash_flow', label: 'Cash Flow' },
    { value: 'trial_balance', label: 'Trial Balance' },
    { value: 'custom', label: 'Custom' },
];

const sectionTypeOptions = [
    { value: 'header', label: 'Header' },
    { value: 'detail', label: 'Detail' },
    { value: 'subtotal', label: 'Subtotal' },
    { value: 'total', label: 'Total' },
    { value: 'separator', label: 'Separator' },
];

const signConventionOptions = [
    { value: 'normal', label: 'Normal' },
    { value: 'reversed', label: 'Reversed' },
];

const getDefaults = (
    entity?: ReportConfiguration | null,
): ReportConfigurationFormData => {
    if (!entity) {
        return {
            code: '',
            name: '',
            description: '',
            report_type: 'balance_sheet',
            is_active: true,
            sections: [],
        };
    }

    return {
        code: entity.code,
        name: entity.name,
        description: entity.description ?? '',
        report_type: entity.report_type,
        is_active: entity.is_active,
        sections: (entity.sections ?? []).map((section) => ({
            id: section.id,
            code: section.code,
            name: section.name,
            sort_order: section.sort_order ?? 0,
            section_type: section.section_type,
            account_type_filter: section.account_type_filter ?? '',
            account_sub_type_filter: section.account_sub_type_filter ?? '',
            sign_convention: section.sign_convention ?? 'normal',
            formula: section.formula ?? '',
            is_active: section.is_active,
            parent_code: section.parent_code ?? '',
        })),
    };
};

export const ReportConfigurationForm = memo<ReportConfigurationFormProps>(
    function ReportConfigurationForm({
        open,
        onOpenChange,
        entity,
        onSubmit,
        isLoading = false,
    }) {
        const isEdit = !!entity;

        const defaultValues = useMemo(() => getDefaults(entity), [entity]);

        const form = useForm<z.input<typeof reportConfigurationFormSchema>>({
            resolver: zodResolver(reportConfigurationFormSchema),
            defaultValues,
        });

        const { fields, append, remove } = useFieldArray({
            control: form.control,
            name: 'sections',
        });

        useEffect(() => {
            if (open) {
                form.reset(defaultValues);
            }
        }, [open, defaultValues, form]);

        const handleAddSection = () => {
            append({
                code: '',
                name: '',
                sort_order: (fields.length + 1) * 10,
                section_type: 'detail',
                account_type_filter: '',
                account_sub_type_filter: '',
                sign_convention: 'normal',
                formula: '',
                is_active: true,
                parent_code: '',
            });
        };

        return (
            <EntityForm
                form={form as UseFormReturn<ReportConfigurationFormData>}
                open={open}
                onOpenChange={onOpenChange}
                title={
                    isEdit
                        ? 'Edit Report Configuration'
                        : 'Add New Report Configuration'
                }
                onSubmit={onSubmit}
                isLoading={isLoading}
                className="sm:max-w-5xl"
            >
                <div className="min-h-0 flex-1 overflow-y-auto sm:pr-4">
                    <div className="space-y-6 py-2">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <InputField
                                name="code"
                                label="Code"
                                placeholder="e.g. balance_sheet"
                                required
                            />
                            <InputField
                                name="name"
                                label="Name"
                                placeholder="Report name"
                                required
                            />
                            <SelectField
                                name="report_type"
                                label="Report Type"
                                options={reportTypeOptions}
                                placeholder="Select report type"
                            />
                            <div className="flex items-center space-x-2 pt-6">
                                <Checkbox
                                    id="is_active"
                                    checked={form.watch('is_active')}
                                    onCheckedChange={(v) =>
                                        form.setValue('is_active', !!v)
                                    }
                                />
                                <Label htmlFor="is_active">Active</Label>
                            </div>
                            <div className="sm:col-span-2">
                                <InputField
                                    name="description"
                                    label="Description"
                                    placeholder="Optional description"
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <h4 className="text-sm font-semibold">
                                    Sections
                                </h4>
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={handleAddSection}
                                >
                                    <Plus className="h-4 w-4" /> Add Section
                                </Button>
                            </div>

                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[90px]">
                                                Order
                                            </TableHead>
                                            <TableHead>Code</TableHead>
                                            <TableHead>Name</TableHead>
                                            <TableHead className="w-[120px]">
                                                Type
                                            </TableHead>
                                            <TableHead className="w-[140px]">
                                                Account Type
                                            </TableHead>
                                            <TableHead className="w-[160px]">
                                                Sub Type
                                            </TableHead>
                                            <TableHead className="w-[110px]">
                                                Sign
                                            </TableHead>
                                            <TableHead>Formula</TableHead>
                                            <TableHead className="w-[120px]">
                                                Parent Code
                                            </TableHead>
                                            <TableHead className="w-[60px]" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {fields.length === 0 ? (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={10}
                                                    className="text-center text-muted-foreground"
                                                >
                                                    No sections defined. Click
                                                    "Add Section" to start.
                                                </TableCell>
                                            </TableRow>
                                        ) : (
                                            fields.map((field, index) => (
                                                <TableRow key={field.id}>
                                                    <TableCell>
                                                        <Input
                                                            type="number"
                                                            {...form.register(
                                                                `sections.${index}.sort_order`,
                                                                {
                                                                    valueAsNumber:
                                                                        true,
                                                                },
                                                            )}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            {...form.register(
                                                                `sections.${index}.code`,
                                                            )}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            {...form.register(
                                                                `sections.${index}.name`,
                                                            )}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <select
                                                            className="w-full rounded-md border px-2 py-1 text-sm"
                                                            {...form.register(
                                                                `sections.${index}.section_type`,
                                                            )}
                                                        >
                                                            {sectionTypeOptions.map(
                                                                (opt) => (
                                                                    <option
                                                                        key={
                                                                            opt.value
                                                                        }
                                                                        value={
                                                                            opt.value
                                                                        }
                                                                    >
                                                                        {
                                                                            opt.label
                                                                        }
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            {...form.register(
                                                                `sections.${index}.account_type_filter`,
                                                            )}
                                                            placeholder="asset"
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            {...form.register(
                                                                `sections.${index}.account_sub_type_filter`,
                                                            )}
                                                            placeholder="current_asset"
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <select
                                                            className="w-full rounded-md border px-2 py-1 text-sm"
                                                            {...form.register(
                                                                `sections.${index}.sign_convention`,
                                                            )}
                                                        >
                                                            {signConventionOptions.map(
                                                                (opt) => (
                                                                    <option
                                                                        key={
                                                                            opt.value
                                                                        }
                                                                        value={
                                                                            opt.value
                                                                        }
                                                                    >
                                                                        {
                                                                            opt.label
                                                                        }
                                                                    </option>
                                                                ),
                                                            )}
                                                        </select>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            {...form.register(
                                                                `sections.${index}.formula`,
                                                            )}
                                                            placeholder="{revenue} - {expense}"
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            {...form.register(
                                                                `sections.${index}.parent_code`,
                                                            )}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                remove(index)
                                                            }
                                                        >
                                                            <Trash className="h-4 w-4 text-destructive" />
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </div>
                </div>
            </EntityForm>
        );
    },
);
