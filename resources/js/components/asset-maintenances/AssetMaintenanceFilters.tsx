import {
    type FieldDescriptor,
    createAsyncSelectFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
    createTextFilterField,
} from '@/components/common/filters';
import { Input } from '@/components/ui/input';

export const createAssetMaintenanceFilterFields = (): FieldDescriptor[] => [
    createTextFilterField('search', 'Search', 'Search maintenances...'),
    ...createAsyncSelectFilterFields([
        {
            name: 'asset_id',
            label: 'Asset',
            url: '/api/assets',
            placeholder: 'Filter by asset',
        },
        {
            name: 'supplier_id',
            label: 'Supplier',
            url: '/api/suppliers',
            placeholder: 'Filter by supplier',
        },
    ]),
    ...createSelectFilterFields([
        {
            name: 'maintenance_type',
            label: 'Type',
            options: [
                { value: 'preventive', label: 'Preventive' },
                { value: 'corrective', label: 'Corrective' },
                { value: 'calibration', label: 'Calibration' },
                { value: 'other', label: 'Other' },
            ],
            placeholder: 'Filter by type',
        },
        {
            name: 'status',
            label: 'Status',
            options: [
                { value: 'scheduled', label: 'Scheduled' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
            placeholder: 'Filter by status',
        },
    ]),
    ...createDateFilterFields([
        {
            name: 'scheduled_from',
            label: 'Scheduled From',
            placeholder: 'Scheduled From',
        },
        {
            name: 'scheduled_to',
            label: 'Scheduled To',
            placeholder: 'Scheduled To',
        },
        {
            name: 'performed_from',
            label: 'Performed From',
            placeholder: 'Performed From',
        },
        {
            name: 'performed_to',
            label: 'Performed To',
            placeholder: 'Performed To',
        },
    ]),
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
