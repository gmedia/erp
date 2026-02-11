'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatDate } from '@/lib/utils';
import { formatRupiah } from '@/utils/formatters';

import { Employee } from '@/types/entity';
import { useTranslation } from '@/contexts/i18n-context';

interface EmployeeViewModalProps {
    open: boolean;
    onClose: () => void;
    item: Employee | null;
}

/**
 * EmployeeViewModal - A read-only modal to display employee details.
 * Similar layout to Edit modal but with static text instead of form inputs.
 */
export const EmployeeViewModal = memo<EmployeeViewModalProps>(
    function EmployeeViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        const departmentName =
            typeof item.department === 'object'
                ? item.department.name
                : item.department;

        const positionName =
            typeof item.position === 'object'
                ? item.position.name
                : item.position;

        const branchName =
            typeof item.branch === 'object'
                ? item.branch.name
                : item.branch;

        return (
            <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
                <DialogContent className="sm:max-w-[425px]">
                    <DialogHeader>
                        <DialogTitle>View Employee</DialogTitle>
                        <DialogDescription>
                            {t('common.view_details')}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        {/* Basic Info Section */}
                        <ViewField label="Name" value={item.name} />
                        <ViewField label="Email" value={item.email} />
                        <ViewField label="Phone" value={item.phone} />

                        {/* Work Info Section */}
                        <ViewField label="Department" value={departmentName} />
                        <ViewField label="Position" value={positionName} />
                        <ViewField label="Branch" value={branchName} />
                        <ViewField
                            label="Salary"
                            value={formatRupiah(item.salary)}
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
