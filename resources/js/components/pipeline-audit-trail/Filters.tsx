import { createAsyncSelectFilterField, createTextFilterField, type FieldDescriptor } from '@/components/common/filters';
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

export function createPipelineAuditTrailFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search entity ID, performer, comment...'),
        // NOTE: We'll use a text field for entity type for simplicity, or it could be a select if we know the exact types
        createTextFilterField('entity_type', 'Entity Type', 'e.g. Asset, Employee'),
        createAsyncSelectFilterField(
            'pipeline_id',
            'Pipeline',
            '/api/pipelines',
            'Select a pipeline',
        ),
        createAsyncSelectFilterField(
            'performed_by',
            'Performed By',
            '/api/users', // Assuming there's a user endpoint, or we can use employees
            'Select a user',
        ),
        createDateFilterField('start_date', 'Start Date'),
        createDateFilterField('end_date', 'End Date'),
    ];
}
