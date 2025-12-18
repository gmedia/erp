'use client';

import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { DEPARTMENTS, POSITIONS } from '@/constants';

type FieldDescriptor = {
    name: string;
    label: string;
    component: React.ReactNode;
};

// Employee-specific filter fields
export function createEmployeeFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            component: <Input placeholder="Search employees..." />,
        },
        {
            name: 'department',
            label: 'Department',
            component: (
                <Select>
                    <SelectTrigger className="border-border bg-background">
                        <SelectValue placeholder="Select a department" />
                    </SelectTrigger>
                    <SelectContent className="border-border bg-background text-foreground">
                        {DEPARTMENTS.map((dept) => (
                            <SelectItem key={dept.value} value={dept.value}>
                                {dept.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ),
        },
        {
            name: 'position',
            label: 'Position',
            component: (
                <Select>
                    <SelectTrigger className="border-border bg-background">
                        <SelectValue placeholder="Select a position" />
                    </SelectTrigger>
                    <SelectContent className="border-border bg-background text-foreground">
                        {POSITIONS.map((pos) => (
                            <SelectItem key={pos.value} value={pos.value}>
                                {pos.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ),
        },
    ];
}
