import {
    createBinaryStatusFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';
import { APPROVABLE_TYPE_OPTIONS } from '@/constants/model-options';

export function createPipelineFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'filter_field.search', fallback: 'Search' },
            {
                key: 'page.pipelines.search_placeholder',
                fallback: 'Search name, code, or description...',
            },
        ),
        createSelectFilterField(
            'entity_type',
            { key: 'filter_field.entity_type', fallback: 'Entity Type' },
            [...APPROVABLE_TYPE_OPTIONS],
            { key: 'filter_field.all_entities', fallback: 'All Entities' },
        ),
        createBinaryStatusFilterField(
            'is_active',
            { key: 'filter_field.status', fallback: 'Status' },
            'true',
            'false',
            { key: 'filter_field.all_statuses', fallback: 'All Statuses' },
        ),
    ];
}
