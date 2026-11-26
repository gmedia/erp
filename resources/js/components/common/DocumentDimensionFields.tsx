'use client';

import AsyncSelectField from '@/components/common/AsyncSelectField';
import type { FormFieldTranslator } from '@/components/common/StatusSelectField';

interface DocumentDimensionFieldsProps {
    readonly fl: FormFieldTranslator;
    readonly placeholders?: {
        readonly branch?: string;
        readonly department?: string;
        readonly fiscalYear?: string;
    };
}

export function DocumentDimensionFields({
    fl,
    placeholders,
}: Readonly<DocumentDimensionFieldsProps>) {
    return (
        <>
            <AsyncSelectField
                name="branch_id"
                label={fl('branch', 'Branch')}
                placeholder={fl(
                    'branch_placeholder',
                    placeholders?.branch ?? 'Select Branch',
                )}
                url="/api/branches"
            />
            <AsyncSelectField
                name="department_id"
                label={fl('department', 'Department')}
                placeholder={fl(
                    'department_placeholder',
                    placeholders?.department ?? 'Select Department (optional)',
                )}
                url="/api/departments"
            />
            <AsyncSelectField
                name="fiscal_year_id"
                label={fl('fiscal_year', 'Fiscal Year')}
                placeholder={fl(
                    'fiscal_year_placeholder',
                    placeholders?.fiscalYear ?? 'Select Fiscal Year',
                )}
                url="/api/fiscal-years"
                preferredMetaKey="preferred_fiscal_year_id"
            />
        </>
    );
}
