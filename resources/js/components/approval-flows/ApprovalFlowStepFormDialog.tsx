import AsyncSelectField from '@/components/common/AsyncSelectField';
import { InputField } from '@/components/common/InputField';
import SelectField from '@/components/common/SelectField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Form } from '@/components/ui/form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

// Since steps validation is grouped with approval flow, we can create a sub-schema for this
const stepSchema = z
    .object({
        id: z.number().optional(),
        name: z.string().min(1, 'Name is required'),
        approver_type: z.enum(['user', 'department_head', 'role']),
        approver_user_id: z.preprocess(
            (val) => (val === '' || val === null ? null : Number(val)),
            z.number().nullable().optional(),
        ),
        approver_role_id: z.preprocess(
            (val) => (val === '' || val === null ? null : Number(val)),
            z.number().nullable().optional(),
        ),
        approver_department_id: z.preprocess(
            (val) => (val === '' || val === null ? null : Number(val)),
            z.number().nullable().optional(),
        ),
        required_action: z.enum(['approve', 'review', 'acknowledge']),
        auto_approve_after_hours: z.preprocess(
            (val) => (val === '' || val === null ? null : Number(val)),
            z.number().nullable().optional(),
        ),
        escalate_after_hours: z.preprocess(
            (val) => (val === '' || val === null ? null : Number(val)),
            z.number().nullable().optional(),
        ),
        escalation_user_id: z.preprocess(
            (val) => (val === '' || val === null ? null : Number(val)),
            z.number().nullable().optional(),
        ),
        can_reject: z
            .union([z.boolean(), z.string()])
            .default(true)
            .transform((val) => val === true || val === 'true'),
    })
    .superRefine((data, ctx) => {
        if (data.approver_type === 'user' && !data.approver_user_id) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message:
                    "User ID is required when Approver Type is 'Specific User'",
                path: ['approver_user_id'],
            });
        }
        if (
            data.approver_type === 'department_head' &&
            !data.approver_department_id
        ) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message:
                    "Department ID is required when Approver Type is 'Department Head'",
                path: ['approver_department_id'],
            });
        }
        if (data.approver_type === 'role' && !data.approver_role_id) {
            ctx.addIssue({
                code: z.ZodIssueCode.custom,
                message:
                    "Role ID is required when Approver Type is 'Specific Role'",
                path: ['approver_role_id'],
            });
        }
    });

export type ApprovalFlowStepFormInput = z.input<typeof stepSchema>;

export type ApprovalFlowStepFormOutput = z.output<typeof stepSchema>;

interface ApprovalFlowStepFormDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    step: ApprovalFlowStepFormInput | null;
    onSave: (data: ApprovalFlowStepFormOutput) => void;
}

export function ApprovalFlowStepFormDialog({
    open,
    onOpenChange,
    step,
    onSave,
}: ApprovalFlowStepFormDialogProps) {
    const defaultValues = useMemo<ApprovalFlowStepFormInput>(() => {
        if (!step) {
            return {
                name: 'New Step',
                approver_type: 'user',
                approver_user_id: '',
                approver_role_id: '',
                approver_department_id: '',
                required_action: 'approve',
                auto_approve_after_hours: '',
                escalate_after_hours: '',
                escalation_user_id: '',
                can_reject: 'true',
            };
        }

        return {
            ...step,
            approver_user_id: step.approver_user_id ?? '',
            approver_role_id: step.approver_role_id ?? '',
            approver_department_id: step.approver_department_id ?? '',
            auto_approve_after_hours: step.auto_approve_after_hours ?? '',
            escalate_after_hours: step.escalate_after_hours ?? '',
            escalation_user_id: step.escalation_user_id ?? '',
            can_reject:
                step.can_reject === false || step.can_reject === 'false'
                    ? 'false'
                    : 'true',
        };
    }, [step]);

    const form = useForm<
        ApprovalFlowStepFormInput,
        unknown,
        ApprovalFlowStepFormOutput
    >({
        resolver: zodResolver(stepSchema),
        defaultValues,
    });

    useEffect(() => {
        if (open) {
            form.reset(defaultValues);
        }
    }, [open, defaultValues, form]);

    const approverType = form.watch('approver_type');

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] max-w-xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {step ? 'Edit Approval Step' : 'Add Approval Step'}
                    </DialogTitle>
                    <DialogDescription className="sr-only">
                        {step
                            ? 'Edit the details of this approval step.'
                            : 'Add a new approval step to the flow.'}
                    </DialogDescription>
                </DialogHeader>

                <Form {...form}>
                    <form
                        onSubmit={(e) => {
                            e.stopPropagation();
                            form.handleSubmit(onSave)(e);
                        }}
                        className="space-y-6"
                    >
                        <div className="grid grid-cols-2 gap-4">
                            <InputField
                                name="name"
                                label="Step Name"
                                placeholder="e.g. Manager Approval"
                            />
                            <SelectField
                                name="required_action"
                                label="Required Action"
                                options={[
                                    { value: 'approve', label: 'Approve' },
                                    { value: 'review', label: 'Review' },
                                    {
                                        value: 'acknowledge',
                                        label: 'Acknowledge',
                                    },
                                ]}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <SelectField
                                name="approver_type"
                                label="Approver Type"
                                options={[
                                    { value: 'user', label: 'Specific User' },
                                    {
                                        value: 'department_head',
                                        label: 'Department Head',
                                    },
                                    { value: 'role', label: 'Specific Role' },
                                ]}
                            />

                            {approverType === 'user' && (
                                <AsyncSelectField
                                    name="approver_user_id"
                                    label="Select User"
                                    url="/api/users"
                                    labelFn={(user: Record<string, unknown>) =>
                                        user.name as string
                                    }
                                    valueFn={(user: Record<string, unknown>) =>
                                        String(user.id)
                                    }
                                    placeholder="Select user..."
                                />
                            )}
                            {approverType === 'department_head' && (
                                <AsyncSelectField
                                    name="approver_department_id"
                                    label="Select Department"
                                    url="/api/departments"
                                    labelFn={(dept: Record<string, unknown>) =>
                                        dept.name as string
                                    }
                                    valueFn={(dept: Record<string, unknown>) =>
                                        String(dept.id)
                                    }
                                    placeholder="Select department..."
                                />
                            )}
                            {approverType === 'role' && (
                                <InputField
                                    name="approver_role_id"
                                    label="Role ID"
                                    type="number"
                                    placeholder="Enter Role ID"
                                />
                            )}
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <InputField
                                name="auto_approve_after_hours"
                                label="Auto Approve After (Hours)"
                                type="number"
                                placeholder="e.g. 48"
                            />
                            <InputField
                                name="escalate_after_hours"
                                label="Escalate After (Hours)"
                                type="number"
                                placeholder="e.g. 24"
                            />
                            <AsyncSelectField
                                name="escalation_user_id"
                                label="Escalate To User"
                                url="/api/users"
                                labelFn={(user: Record<string, unknown>) =>
                                    user.name as string
                                }
                                valueFn={(user: Record<string, unknown>) =>
                                    String(user.id)
                                }
                                placeholder="Select user..."
                            />
                            <SelectField
                                name="can_reject"
                                label="Can Reject"
                                options={[
                                    { value: 'true', label: 'Yes' },
                                    { value: 'false', label: 'No' },
                                ]}
                            />
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit">Save Step</Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
