'use client';

import { memo } from 'react';
import { SimpleEntityViewModal } from '@/components/common/SimpleEntityViewModal';

interface DepartmentViewModalProps {
    open: boolean;
    onClose: () => void;
    item: {
        id: number;
        name: string;
        created_at: string;
        updated_at: string;
    } | null;
}

/**
 * DepartmentViewModal - A read-only modal to display department details.
 */
export const DepartmentViewModal = memo<DepartmentViewModalProps>(
    function DepartmentViewModal(props) {
        return <SimpleEntityViewModal {...props} entityName="Department" />;
    },
);
