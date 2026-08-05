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

export const EmployeeViewModal = memo<EmployeeViewModalProps>(
    function EmployeeViewModal({ open, onClose, item }) {
        const { t } = useTranslation();
        if (!item) return null;

        const employment = item.current_employment;
        const status = employment?.employment_status;
        const statusLabels: Record<string, string> = {
            intern: 'Intern',
            regular: 'Regular',
        };
        const statusLabel = (status ? statusLabels[status] : undefined) ?? '-';

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
                    <ViewField label="Status" value={statusLabel} />
                    <ViewField
                        label="Department"
                        value={employment?.department?.name ?? '-'}
                    />
                    <ViewField
                        label="Position"
                        value={employment?.position?.name ?? '-'}
                    />
                    <ViewField
                        label="Branch"
                        value={employment?.branch?.name ?? '-'}
                    />
                    <ViewField
                        label="Salary"
                        value={formatRupiah(employment?.salary || 0)}
                    />
                    <ViewField
                        label="Hire Date"
                        value={
                            employment?.hire_date
                                ? formatDate(employment.hire_date)
                                : '-'
                        }
                    />
                    {employment?.termination_date && (
                        <ViewField
                            label="Termination Date"
                            value={formatDate(employment.termination_date)}
                        />
                    )}
                </div>
            </ViewModalShell>
        );
    },
);
