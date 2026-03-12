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
const stepSchema = z.object({
    id: z.number().optional(),
    name: z.string().min(1, 'Name is required'),
    approver_type: z.literal('user'),
    approver_user_id: z.preprocess(
        (val) => (val === '' || val === null ? null : Number(val)),
        z
            .number()
            .nullable()
            .refine((value) => value !== null, {
                message: 'Approver user is required',
            }),
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
                            <AsyncSelectField
                                name="approver_user_id"
                                label="Approver"
                                url="/api/users"
                                labelFn={(user: Record<string, unknown>) =>
                                    user.name as string
                                }
                                valueFn={(user: Record<string, unknown>) =>
                                    String(user.id)
                                }
                                placeholder="Select approver..."
                            />
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
