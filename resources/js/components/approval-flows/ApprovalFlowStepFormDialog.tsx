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
import { ScrollArea } from '@/components/ui/scroll-area';
import { useTranslation } from '@/contexts/i18n-context';
import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { type input, type output } from 'zod';

import { approvalFlowStepSchema } from '@/utils/schemas';

export type ApprovalFlowStepFormInput = input<typeof approvalFlowStepSchema>;

export type ApprovalFlowStepFormOutput = output<typeof approvalFlowStepSchema>;

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
}: Readonly<ApprovalFlowStepFormDialogProps>) {
    const { t } = useTranslation();
    const fl = (key: string, fallback: string) => {
        const translated = t(`form_fields.approval_flow_steps.${key}`);
        return translated === `form_fields.approval_flow_steps.${key}`
            ? fallback
            : translated;
    };
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
        resolver: zodResolver(approvalFlowStepSchema),
        defaultValues,
    });

    useEffect(() => {
        if (open) {
            form.reset(defaultValues);
        }
    }, [open, defaultValues, form]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] max-w-xl flex-col overflow-hidden">
                <DialogHeader>
                    <DialogTitle>
                        {step
                            ? fl('dialog_edit_title', 'Edit Approval Step')
                            : fl('dialog_add_title', 'Add Approval Step')}
                    </DialogTitle>
                    <DialogDescription className="sr-only">
                        {step
                            ? fl(
                                  'dialog_edit_description',
                                  'Edit the details of this approval step.',
                              )
                            : fl(
                                  'dialog_add_description',
                                  'Add a new approval step to the flow.',
                              )}
                    </DialogDescription>
                </DialogHeader>

                <Form {...form}>
                    <form
                        onSubmit={(e) => {
                            e.stopPropagation();
                            void form.handleSubmit(onSave)(e);
                        }}
                        className="flex min-h-0 flex-1 flex-col"
                    >
                        <ScrollArea className="flex-1 pr-4">
                            <div className="space-y-6 py-1">
                                <div className="grid grid-cols-2 gap-4">
                                    <InputField
                                        name="name"
                                        label={fl('name', 'Step Name')}
                                        placeholder={fl(
                                            'name_placeholder',
                                            'e.g. Manager Approval',
                                        )}
                                    />
                                    <SelectField
                                        name="required_action"
                                        label={fl(
                                            'required_action',
                                            'Required Action',
                                        )}
                                        options={[
                                            {
                                                value: 'approve',
                                                label: fl(
                                                    'action_approve',
                                                    'Approve',
                                                ),
                                            },
                                            {
                                                value: 'review',
                                                label: fl(
                                                    'action_review',
                                                    'Review',
                                                ),
                                            },
                                            {
                                                value: 'acknowledge',
                                                label: fl(
                                                    'action_acknowledge',
                                                    'Acknowledge',
                                                ),
                                            },
                                        ]}
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <AsyncSelectField
                                        name="approver_user_id"
                                        label={fl('approver', 'Approver')}
                                        url="/api/users"
                                        labelFn={(
                                            user: Record<string, unknown>,
                                        ) => user.name as string}
                                        valueFn={(
                                            user: Record<string, unknown>,
                                        ) => String(user.id)}
                                        placeholder={fl(
                                            'approver_placeholder',
                                            'Select approver...',
                                        )}
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <InputField
                                        name="auto_approve_after_hours"
                                        label={fl(
                                            'auto_approve_after_hours',
                                            'Auto Approve After (Hours)',
                                        )}
                                        type="number"
                                        placeholder={fl(
                                            'auto_approve_after_hours_placeholder',
                                            'e.g. 48',
                                        )}
                                    />
                                    <InputField
                                        name="escalate_after_hours"
                                        label={fl(
                                            'escalate_after_hours',
                                            'Escalate After (Hours)',
                                        )}
                                        type="number"
                                        placeholder={fl(
                                            'escalate_after_hours_placeholder',
                                            'e.g. 24',
                                        )}
                                    />
                                    <AsyncSelectField
                                        name="escalation_user_id"
                                        label={fl(
                                            'escalation_user',
                                            'Escalate To User',
                                        )}
                                        url="/api/users"
                                        labelFn={(
                                            user: Record<string, unknown>,
                                        ) => user.name as string}
                                        valueFn={(
                                            user: Record<string, unknown>,
                                        ) => String(user.id)}
                                        placeholder={fl(
                                            'escalation_user_placeholder',
                                            'Select user...',
                                        )}
                                    />
                                    <SelectField
                                        name="can_reject"
                                        label={fl('can_reject', 'Can Reject')}
                                        options={[
                                            {
                                                value: 'true',
                                                label: fl(
                                                    'can_reject_yes',
                                                    'Yes',
                                                ),
                                            },
                                            {
                                                value: 'false',
                                                label: fl(
                                                    'can_reject_no',
                                                    'No',
                                                ),
                                            },
                                        ]}
                                    />
                                </div>
                            </div>
                        </ScrollArea>

                        <DialogFooter className="shrink-0 pt-4">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => onOpenChange(false)}
                            >
                                {fl('cancel', 'Cancel')}
                            </Button>
                            <Button type="submit">
                                {fl('save', 'Save Step')}
                            </Button>
                        </DialogFooter>
                    </form>
                </Form>
            </DialogContent>
        </Dialog>
    );
}
