import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ApprovalFlowFormData } from '@/utils/schemas';
import { Edit, Plus, Trash } from 'lucide-react';
import { useState } from 'react';
import { UseFieldArrayReturn } from 'react-hook-form';
import { ApprovalFlowStepFormDialog } from './ApprovalFlowStepFormDialog';

interface ApprovalFlowStepManagerProps {
    fieldArrayProps: UseFieldArrayReturn<ApprovalFlowFormData, 'steps', 'id'>;
}

export function ApprovalFlowStepManager({
    fieldArrayProps,
}: ApprovalFlowStepManagerProps) {
    const { fields, append, remove, update } = fieldArrayProps;

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

    const handleDelete = (index: number) => {
        if (window.confirm('Are you sure you want to delete this step?')) {
            remove(index);
        }
    };

    const renderRow = (
        step: NonNullable<ApprovalFlowFormData['steps']>[number],
        index: number,
    ) => {
        let approverLabel = '-';
        if (step.approver_type === 'user') {
            approverLabel = `User ID: ${step.approver_user_id || '-'}`;
        } else if (step.approver_type === 'department_head') {
            approverLabel = `Dept ID: ${step.approver_department_id || '-'}`;
        } else if (step.approver_type === 'role') {
            approverLabel = `Role ID: ${step.approver_role_id || '-'}`;
        }

        return (
            <TableRow key={step.id}>
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
                        title="Edit step"
                    >
                        <Edit className="h-4 w-4" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => handleDelete(index)}
                        title="Delete step"
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
                <h3 className="text-lg font-medium">Approval Steps</h3>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={handleCreateNew}
                >
                    <Plus className="mr-2 h-4 w-4" />
                    Add Step
                </Button>
            </div>

            <div className="overflow-hidden rounded-md border border-border">
                <Table>
                    <TableHeader className="bg-muted/50">
                        <TableRow>
                            <TableHead className="w-12">#</TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>Required Action</TableHead>
                            <TableHead>Approver Type</TableHead>
                            <TableHead>Target</TableHead>
                            <TableHead className="text-right">Manage</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {fields.map((field, index) => renderRow(field, index))}
                        {!fields.length && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-6 text-center text-muted-foreground"
                                >
                                    No steps defined. Add one to enable approval
                                    flow.
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
                    step={
                        editingIndex !== null && fields[editingIndex]
                            ? fields[editingIndex]
                            : null
                    }
                    onSave={(data) => {
                        if (editingIndex !== null) {
                            update(editingIndex, data);
                        } else {
                            append(data);
                        }
                        setIsDialogOpen(false);
                    }}
                />
            )}
        </div>
    );
}
