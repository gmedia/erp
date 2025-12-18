'use client';

import { Input } from '@/components/ui/input';

type FieldDescriptor = {
    name: string;
    label: string;
    component: React.ReactNode;
};

// Department-specific filter fields
export function createDepartmentFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            component: <Input placeholder="Search departments..." />,
        },
    ];
}
