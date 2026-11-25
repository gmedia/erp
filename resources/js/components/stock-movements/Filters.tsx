import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createProductWarehouseFilterFields,
    createSelectFilterField,
    createTextFilterField,
    stockMovementTypeOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockMovementsFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search reference, notes, product, warehouse...',
        ),
        ...createProductWarehouseFilterFields(),
        createSelectFilterField(
            'movement_type',
            { key: 'column.movement_type', fallback: 'Movement Type' },
            stockMovementTypeOptions,
            'Select movement type',
        ),
        {
            name: 'start_date',
            label: { key: 'column.start_date', fallback: 'Start Date' },
            component: <FilterDatePicker placeholder="Start Date" />,
        },
        {
            name: 'end_date',
            label: { key: 'column.end_date', fallback: 'End Date' },
            component: <FilterDatePicker placeholder="End Date" />,
        },
    ];
}
