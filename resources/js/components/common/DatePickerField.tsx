'use client';

import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';

interface DatePickerFieldProps {
    name: string;
    label: string;
    placeholder?: string;
    disabled?: (date: Date) => boolean;
    className?: string;
}

/**
 * Renders a Calendar+Popover date picker as a react-hook-form FormField.
 * Stores the value as a yyyy-MM-dd string to match project-wide Zod schemas.
 */
export function DatePickerField({
    name,
    label,
    placeholder = 'Pick a date',
    disabled,
    className,
}: Readonly<DatePickerFieldProps>) {
    return (
        <FormField
            name={name}
            render={({ field }) => {
                const dateValue = field.value
                    ? new Date(field.value)
                    : undefined;

                const handleSelect = (newDate: Date | undefined) => {
                    field.onChange(
                        newDate ? format(newDate, 'yyyy-MM-dd') : '',
                    );
                };

                return (
                    <FormItem className={className}>
                        <FormLabel>{label}</FormLabel>
                        <Popover>
                            <PopoverTrigger asChild>
                                <FormControl>
                                    <Button
                                        variant="outline"
                                        className={cn(
                                            'w-full pl-3 text-left font-normal',
                                            !field.value &&
                                                'text-muted-foreground',
                                        )}
                                    >
                                        {field.value ? (
                                            formatDateByRegionalSettings(
                                                new Date(field.value),
                                            )
                                        ) : (
                                            <span>{placeholder}</span>
                                        )}
                                        <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                                    </Button>
                                </FormControl>
                            </PopoverTrigger>
                            <PopoverContent
                                className="w-auto p-0"
                                align="start"
                            >
                                <Calendar
                                    mode="single"
                                    selected={dateValue}
                                    onSelect={handleSelect}
                                    disabled={disabled}
                                    autoFocus
                                />
                            </PopoverContent>
                        </Popover>
                        <FormMessage />
                    </FormItem>
                );
            }}
        />
    );
}
