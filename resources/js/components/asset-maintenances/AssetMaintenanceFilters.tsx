import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    type FieldDescriptor,
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
} from '@/components/common/filters';
import { Input } from '@/components/ui/input';

export const createAssetMaintenanceFilterFields = (): FieldDescriptor[] => [
    createTextFilterField('search', 'Search', 'Search maintenances...'),
    createAsyncSelectFilterField('asset_id', 'Asset', '/api/assets', 'Filter by asset'),
    createSelectFilterField(
        'maintenance_type',
        'Type',
        [
            { value: 'preventive', label: 'Preventive' },
            { value: 'corrective', label: 'Corrective' },
            { value: 'calibration', label: 'Calibration' },
            { value: 'other', label: 'Other' },
        ],
        'Filter by type',
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
        'Filter by status',
    ),
    createAsyncSelectFilterField('supplier_id', 'Supplier', '/api/suppliers', 'Filter by supplier'),
    {
        name: 'scheduled_from',
        label: 'Scheduled From',
        component: <FilterDatePicker placeholder="Scheduled From" />,
    },
    {
        name: 'scheduled_to',
        label: 'Scheduled To',
        component: <FilterDatePicker placeholder="Scheduled To" />,
    },
    {
        name: 'performed_from',
        label: 'Performed From',
        component: <FilterDatePicker placeholder="Performed From" />,
    },
    {
        name: 'performed_to',
        label: 'Performed To',
        component: <FilterDatePicker placeholder="Performed To" />,
    },
    {
        name: 'cost_min',
        label: 'Min Cost',
        component: <Input type="number" min={0} placeholder="0" />,
    },
    {
        name: 'cost_max',
        label: 'Max Cost',
        component: <Input type="number" min={0} placeholder="0" />,
    },
];
