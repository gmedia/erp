'use client';

import EntityForm from '@/components/common/EntityForm';
import NameField from '@/components/common/NameField';
import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import * as z from 'zod';

const formSchema = z.object({
    name: z.string().min(2, { message: 'Name must be at least 2 characters.' }),
});

interface Department {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

interface DepartmentFormData {
    name: string;
}

interface DepartmentFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    department?: Department | null;
    onSubmit: (data: DepartmentFormData) => void;
    isLoading?: boolean;
}

export function DepartmentForm({
    open,
    onOpenChange,
    department,
    onSubmit,
    isLoading = false,
}: DepartmentFormProps) {
    const form = useForm({
        resolver: zodResolver(formSchema),
        defaultValues: department
            ? { name: department.name }
            : { name: '' },
    });

    return (
        <EntityForm
            form={form}
            open={open}
            onOpenChange={onOpenChange}
            title={department ? 'Edit Department' : 'Add New Department'}
            onSubmit={onSubmit}
            schema={formSchema}
            isLoading={isLoading}
        >
            <NameField name="name" label="Name" placeholder="e.g., Human Resources" />
        </EntityForm>
    );
}
