'use client';

import { memo } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDate } from '@/lib/utils';

import { Employee } from '@/types/entity';

interface EmployeeViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Employee | null;
}

/**
 * Helper function to format currency values
 */
const formatCurrency = (value: string | number): string => {
    const numValue = typeof value === 'number' ? value : parseFloat(value);
    if (isNaN(numValue)) return '-';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(numValue);
};

/**
 * Individual field display component for consistent styling
 */
const ViewField = ({
    label,
    value,
}: {
    label: string;
    value: React.ReactNode;
}) => (
    <div className="space-y-1">
        <label className="text-sm font-medium text-muted-foreground">
            {label}
        </label>
        <p className="text-sm font-medium">{value || '-'}</p>
    </div>
);

/**
 * EmployeeViewModal - A read-only modal to display employee details.
 * Similar layout to Edit modal but with static text instead of form inputs.
 */
export const EmployeeViewModal = memo<EmployeeViewModalProps>(
    function EmployeeViewModal({ open, onClose, item }) {
        if (!item) return null;

        const departmentName =
            typeof item.department === 'object'
                ? item.department.name
                : item.department;

        const positionName =
            typeof item.position === 'object'
                ? item.position.name
                : item.position;

        return (
            <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>View Employee</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        {/* Basic Info Section */}
                        <ViewField label="Name" value={item.name} />
                        <ViewField label="Email" value={item.email} />
                        <ViewField label="Phone" value={item.phone} />

                        {/* Work Info Section */}
                        <ViewField label="Department" value={departmentName} />
                        <ViewField label="Position" value={positionName} />
                        <ViewField
                            label="Salary"
                            value={formatCurrency(item.salary)}
                        />

                        {/* Date Section */}
                        <ViewField
                            label="Hire Date"
                            value={formatDate(item.hire_date)}
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" onClick={onClose}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        );
    },
);
