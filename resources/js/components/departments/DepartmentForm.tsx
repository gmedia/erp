'use client';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import { FormMessage } from '@/components/ui/form';
import { Department, DepartmentFormData } from '@/types/department';
import { zodResolver } from '@hookform/resolvers/zod';
import * as React from 'react';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

const formSchema = z.object({
    name: z
        .string()
        .min(2, { message: 'Name must be at least 2 characters.' })
        .max(255, { message: 'Maximum 255 characters.' }),
});

export function DepartmentForm({
    open,
    onOpenChange,
    department,
    onSubmit,
    isLoading = false,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    department?: Department | null;
    onSubmit: (data: DepartmentFormData) => void;
    isLoading?: boolean;
}) {
    const defaultValues = department ? { name: department.name } : undefined;

    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: defaultValues as any,
    });
    const { _control, reset } = form;

    // Reset form values when the selected department changes (edit mode)
    React.useEffect(() => {
        if (department) {
            reset({ name: department.name });
        } else {
            reset({});
        }
    }, [department, reset]);

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={department ? 'Edit Department' : 'Add New Department'}
            onSubmit={onSubmit}
            defaultValues={defaultValues}
            schema={formSchema}
            isLoading={isLoading}
        >
            <NameField name="name" label="Name" placeholder="e.g., Marketing">
                <FormMessage />
            </NameField>
        </EntityForm>
    );
}
