'use client';

import { SimpleEntityForm, SimpleEntityFormData } from '@/components/common/EntityForm';
import { Position } from '@/types/position';

interface PositionFormProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    position?: Position | null;
    onSubmit: (data: SimpleEntityFormData) => void;
    isLoading?: boolean;
}

export function PositionForm({
    open,
    onOpenChange,
    position,
    onSubmit,
    isLoading = false,
}: PositionFormProps) {
    return (
        <SimpleEntityForm
            open={open}
            onOpenChange={onOpenChange}
            entity={position}
            onSubmit={onSubmit}
            isLoading={isLoading}
            entityName="Position"
        />
    );
}
