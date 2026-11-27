'use client';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';

interface FilterDatePickerProps {
    label?: string;
    /** Tampilkan label sebagai prefix di dalam tombol (toolbar satu baris). */
    inlineLabel?: boolean;
    value?: string;
    onChange?: (e: { target: { value: string } }) => void;
    placeholder?: string;
    className?: string;
}

export function FilterDatePicker({
    label,
    inlineLabel = false,
    value,
    onChange,
    placeholder = 'Pick a date',
    className,
}: Readonly<FilterDatePickerProps>) {
    // Safely parse date
    const parsedDate = value ? Date.parse(value) : Number.NaN;
    const date =
        value && !Number.isNaN(parsedDate) ? new Date(parsedDate) : undefined;

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
        <div className="space-y-2">
            {label && !inlineLabel && (
                <label className="text-sm font-medium">{label}</label>
            )}
            <Popover>
                <PopoverTrigger asChild>
                    <Button
                        variant="outline"
                        className={cn(
                            'h-8 w-full justify-start rounded-[6px] border-transparent bg-secondary px-2.5 text-left text-sm font-normal hover:bg-secondary focus-visible:ring-2 focus-visible:ring-[2px] focus-visible:ring-ring/40',
                            !date && 'text-muted-foreground',
                            className,
                        )}
                    >
                        <CalendarIcon className="mr-2 h-4 w-4" />
                        {label && inlineLabel && (
                            <span className="mr-1.5 text-muted-foreground">
                                {label}
                            </span>
                        )}
                        {date ? (
                            formatDateByRegionalSettings(date)
                        ) : (
                            <span>{placeholder}</span>
                        )}
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                    <Calendar
                        mode="single"
                        selected={date}
                        onSelect={handleSelect}
                        autoFocus
                    />
                </PopoverContent>
            </Popover>
        </div>
    );
}
