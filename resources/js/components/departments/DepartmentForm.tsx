'use client';

import { SimpleEntityForm, SimpleEntityFormData } from '@/components/common/EntityForm';
import { Department } from '@/types/department';

interface DepartmentFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    department?: Department | null;
    onSubmit: (data: SimpleEntityFormData) => void;
    isLoading?: boolean;
}

export function DepartmentForm({
    open,
    onOpenChange,
    department,
    onSubmit,
    isLoading = false,
}: DepartmentFormProps) {
    return (
        <SimpleEntityForm
            open={open}
            onOpenChange={onOpenChange}
            entity={department}
            onSubmit={onSubmit}
            isLoading={isLoading}
            entityName="Department"
        />
    );
}
