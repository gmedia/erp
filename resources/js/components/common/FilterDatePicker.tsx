'use client';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';

interface FilterDatePickerProps {
    value?: string;
    onChange?: (e: { target: { value: string } }) => void;
    placeholder?: string;
    className?: string;
}

export function FilterDatePicker({
    value,
    onChange,
    placeholder = 'Pick a date',
    className,
}: FilterDatePickerProps) {
    // Safely parse date
    const date = value && !isNaN(Date.parse(value)) ? new Date(value) : undefined;

    const handleSelect = (newDate: Date | undefined) => {
        if (onChange) {
            onChange({
                target: {
                    value: newDate ? format(newDate, 'yyyy-MM-dd') : '',
                },
            });
        }
    };

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    className={cn(
                        'w-full justify-start text-left font-normal',
                        !date && 'text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarIcon className="mr-2 h-4 w-4" />
                    {date ? format(date, 'PPP') : <span>{placeholder}</span>}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <Calendar
                    mode="single"
                    selected={date}
                    onSelect={handleSelect}
                    initialFocus
                />
            </PopoverContent>
        </Popover>
    );
}
