'use client';

import { memo } from 'react';
import { SimpleEntityViewModal } from '@/components/common/SimpleEntityViewModal';

interface PositionViewModalProps {
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
 * PositionViewModal - A read-only modal to display position details.
 */
export const PositionViewModal = memo<PositionViewModalProps>(
    function PositionViewModal(props) {
        return <SimpleEntityViewModal {...props} entityName="Position" />;
    },
);
