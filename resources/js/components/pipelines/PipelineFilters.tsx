import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';
import { APPROVABLE_TYPE_OPTIONS } from '@/constants/model-options';

export function createPipelineFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search name, code, or description...',
        ),
        createSelectFilterField(
            'entity_type',
            'Entity Type',
            [...APPROVABLE_TYPE_OPTIONS],
            'All Entities',
        ),
        createSelectFilterField(
            'is_active',
            'Status',
            [
                { value: 'true', label: 'Active' },
                { value: 'false', label: 'Inactive' },
            ],
            'All Statuses',
        ),
    ];
}
