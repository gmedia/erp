import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/contexts/i18n-context';
import { Edit, Plus, Trash } from 'lucide-react';
import { useState } from 'react';
import { UseFieldArrayReturn } from 'react-hook-form';
import type { ApprovalFlowFormInput } from './ApprovalFlowForm';
import {
    ApprovalFlowStepFormDialog,
    type ApprovalFlowStepFormOutput,
} from './ApprovalFlowStepFormDialog';

type ApprovalFlowStepInput = NonNullable<
    ApprovalFlowFormInput['steps']
>[number];

type StepField = ApprovalFlowStepInput & {
    fieldId: string;
};

interface ApprovalFlowStepManagerProps {
    fieldArrayProps: UseFieldArrayReturn<
        ApprovalFlowFormInput,
        'steps',
        'fieldId'
    >;
    errorMessage?: string;
}

function toStepInput(data: ApprovalFlowStepFormOutput): ApprovalFlowStepInput {
    return {
        id: data.id,
        name: data.name,
        approver_type: data.approver_type,
        approver_user_id: data.approver_user_id ?? null,
        required_action: data.required_action,
        auto_approve_after_hours: data.auto_approve_after_hours ?? null,
        escalate_after_hours: data.escalate_after_hours ?? null,
        escalation_user_id: data.escalation_user_id ?? null,
        can_reject: data.can_reject,
    };
}

export function ApprovalFlowStepManager({
    fieldArrayProps,
    errorMessage,
}: Readonly<ApprovalFlowStepManagerProps>) {
    const { fields, append, remove, update } = fieldArrayProps;
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.approval_flow_steps.${key}`);
        return translated === `form_fields.approval_flow_steps.${key}`
            ? fallback
            : translated;
    };

    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [editingIndex, setEditingIndex] = useState<number | null>(null);

    const handleCreateNew = () => {
        setEditingIndex(null);
        setIsDialogOpen(true);
    };

    const handleEdit = (index: number) => {
        setEditingIndex(index);
        setIsDialogOpen(true);
    };

    const selectedStep =
        editingIndex === null ? null : (fields[editingIndex] ?? null);

    const handleDelete = (index: number) => {
        if (
            globalThis.confirm(
                fl(
                    'delete_confirm',
                    'Are you sure you want to delete this step?',
                ),
            )
        ) {
            remove(index);
        }
    };

    const renderRow = (step: StepField, index: number) => {
        const approverValue = step.approver_user_id;
        const approverLabel =
            typeof approverValue === 'string' ||
            typeof approverValue === 'number'
                ? fl('user_id_label', 'User ID: {id}').replace(
                      '{id}',
                      String(approverValue),
                  )
                : fl('user_id_empty', 'User ID: -');

        return (
            <TableRow key={step.fieldId}>
                <TableCell>{index + 1}</TableCell>
                <TableCell>{step.name}</TableCell>
                <TableCell className="capitalize">
                    {step.required_action}
                </TableCell>
                <TableCell className="capitalize">
                    {step.approver_type?.replace('_', ' ')}
                </TableCell>
                <TableCell>{approverLabel}</TableCell>
                <TableCell className="text-right">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => handleEdit(index)}
                        title={fl('edit_step', 'Edit step')}
                    >
                        <Edit className="h-4 w-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(index)}
                        title={fl('delete_step', 'Delete step')}
                        disabled={fields.length === 1}
                    >
                        <Trash className="h-4 w-4" />
                    </Button>
                </TableCell>
            </TableRow>
        );
    };

    return (
        <div className="mt-8 border-t border-border pt-6">
            <div className="mb-4 flex items-center justify-between">
                <div>
                    <h3 className="text-lg font-medium">
                        {fl('manager_title', 'Approval Steps')}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {fl(
                            'manager_description',
                            'Define the ordered steps that will receive and process this approval.',
                        )}
                    </p>
                    {errorMessage && (
                        <p className="mt-2 text-sm font-medium text-destructive">
                            {errorMessage}
                        </p>
                    )}
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={handleCreateNew}
                >
                    <Plus className="mr-2 h-4 w-4" />
                    {fl('add_step', 'Add Step')}
                </Button>
            </div>

            <div className="overflow-hidden rounded-md border border-border">
                <Table>
                    <TableHeader className="bg-muted/50">
                        <TableRow>
                            <TableHead className="w-12">
                                {fl('column_number', '#')}
                            </TableHead>
                            <TableHead>{fl('column_name', 'Name')}</TableHead>
                            <TableHead>
                                {fl(
                                    'column_required_action',
                                    'Required Action',
                                )}
                            </TableHead>
                            <TableHead>
                                {fl('column_approver_type', 'Approver Type')}
                            </TableHead>
                            <TableHead>
                                {fl('column_target', 'Target')}
                            </TableHead>
                            <TableHead className="text-right">
                                {fl('column_manage', 'Manage')}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {fields.map((field, index) => renderRow(field, index))}
                        {fields.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-6 text-center text-muted-foreground"
                                >
                                    {fl(
                                        'empty',
                                        'No steps defined. Add one to enable approval flow.',
                                    )}
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {isDialogOpen && (
                <ApprovalFlowStepFormDialog
                    open={isDialogOpen}
                    onOpenChange={setIsDialogOpen}
                    step={selectedStep}
                    onSave={(data) => {
                        const nextStep = toStepInput(data);

                        if (editingIndex === null) {
                            append(nextStep);
                        } else {
                            update(editingIndex, nextStep);
                        }
                        setIsDialogOpen(false);
                    }}
                />
            )}
        </div>
    );
}
