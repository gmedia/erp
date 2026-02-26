import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import { createAsyncSelectFilterField, createTextFilterField, type FieldDescriptor } from '@/components/common/filters';

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
        {
            name: 'start_date',
            label: 'Start Date',
            component: <FilterDatePicker placeholder="Start Date" />,
        },
        {
            name: 'end_date',
            label: 'End Date',
            component: <FilterDatePicker placeholder="End Date" />,
        },
    ];
}
