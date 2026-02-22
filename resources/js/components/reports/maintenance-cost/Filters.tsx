import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';
import { Input } from '@/components/ui/input';
import * as React from 'react';

export function createDateFilterField(
    name: string,
    label: string,
): FieldDescriptor {
    return {
        name,
        label,
        component: <Input type="date" className="block w-full" />,
    };
}

export function createMaintenanceCostReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search code, name, notes...'),
        createAsyncSelectFilterField(
            'asset_category_id',
            'Category',
            '/api/asset-categories',
            'Select a category',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select a branch',
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            'Vendor',
            '/api/suppliers',
            'Select a vendor',
        ),
        createSelectFilterField(
            'maintenance_type',
            'Type',
            [
                { value: 'preventive', label: 'Preventive' },
                { value: 'corrective', label: 'Corrective' },
                { value: 'calibration', label: 'Calibration' },
                { value: 'other', label: 'Other' },
            ],
            'Select type'
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'scheduled', label: 'Scheduled' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
            'Select status'
        ),
        createDateFilterField('start_date', 'Start Date'),
        createDateFilterField('end_date', 'End Date'),
    ];
}
