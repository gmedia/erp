'use client';

import * as React from 'react';
import { Input } from '@/components/ui/input';

export type FieldDescriptor = {
    name: string;
    label: string;
    component: React.ReactNode;
};

// Generic filter fields for simple entities
export function createSimpleEntityFilterFields(placeholder: string): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            component: <Input placeholder={placeholder} />,
        },
    ];
}
