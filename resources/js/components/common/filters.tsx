'use client';

import * as React from 'react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type FieldDescriptor = {
    name: string;
    label: string;
    component: React.ReactNode;
};

export type SelectOption = {
    value: string;
    label: string;
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

// Generic select filter field creator
export function createSelectFilterField(
    name: string,
    label: string,
    options: SelectOption[],
    placeholder: string
): FieldDescriptor {
    return {
        name,
        label,
        component: (
            <Select>
                <SelectTrigger className="border-border bg-background">
                    <SelectValue placeholder={placeholder} />
                </SelectTrigger>
                <SelectContent className="border-border bg-background text-foreground">
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        ),
    };
}

// Generic text input filter field creator
export function createTextFilterField(
    name: string,
    label: string,
    placeholder: string
): FieldDescriptor {
    return {
        name,
        label,
        component: <Input placeholder={placeholder} />,
    };
}
