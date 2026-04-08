'use client';

import { memo } from 'react';

import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { formatDate } from '@/lib/utils';
import { formatRupiah } from '@/utils/formatters';

import { useTranslation } from '@/contexts/i18n-context';
import { Employee } from '@/types/entity';

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
            typeof item.branch === 'object' ? item.branch.name : item.branch;

        return (
            <ViewModalShell
                open={open}
                onClose={onClose}
                title="View Employee"
                description={t('common.view_details')}
            >
                <div className="space-y-4 py-4">
                    <ViewField label="NIK" value={item.employee_id} />
                    <ViewField label="Name" value={item.name} />
                    <ViewField label="Email" value={item.email} />
                    <ViewField label="Phone" value={item.phone} />
                    <ViewField
                        label="Status"
                        value={
                            item.employment_status === 'intern'
                                ? 'Intern'
                                : 'Regular'
                        }
                    />
                    <ViewField label="Department" value={departmentName} />
                    <ViewField label="Position" value={positionName} />
                    <ViewField label="Branch" value={branchName} />
                    <ViewField
                        label="Salary"
                        value={formatRupiah(item.salary || 0)}
                    />
                    <ViewField label="Hire Date" value={formatDate(item.hire_date)} />
                    {item.termination_date && (
                        <ViewField
                            label="Termination Date"
                            value={formatDate(item.termination_date)}
                        />
                    )}
                </div>
            </ViewModalShell>
        );
    },
);
