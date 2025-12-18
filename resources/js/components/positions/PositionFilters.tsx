'use client';

import { Input } from '@/components/ui/input';

type FieldDescriptor = {
    name: string;
    label: string;
    component: React.ReactNode;
};

// Position-specific filter fields
export function createPositionFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            component: <Input placeholder="Search positions..." />,
        },
    ];
}
