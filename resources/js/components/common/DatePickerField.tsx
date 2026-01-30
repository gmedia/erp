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
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';

interface DatePickerFieldProps {
    name: string;
    label: string;
    placeholder?: string;
    disabled?: (date: Date) => boolean;
    className?: string;
}

export function DatePickerField({
    name,
    label,
    placeholder = 'Pick a date',
    disabled,
    className,
}: DatePickerFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={className}>
                    <FormLabel>{label}</FormLabel>
                    <Popover>
                        <PopoverTrigger asChild>
                            <FormControl>
                                <Button
                                    variant="outline"
                                    className={cn(
                                        'w-full pl-3 text-left font-normal',
                                        !field.value && 'text-muted-foreground',
                                    )}
                                >
                                    {field.value ? (
                                        format(
                                            field.value as unknown as Date,
                                            'PPP',
                                        )
                                    ) : (
                                        <span>{placeholder}</span>
                                    )}
                                    <CalendarIcon className="ml-auto h-4 w-4 opacity-50" />
                                </Button>
                            </FormControl>
                        </PopoverTrigger>
                        <PopoverContent className="w-auto p-0" align="start">
                            <Calendar
                                mode="single"
                                selected={field.value as Date | undefined}
                                onSelect={
                                    field.onChange as (
                                        date: Date | undefined,
                                    ) => void
                                }
                                disabled={disabled}
                                initialFocus
                            />
                        </PopoverContent>
                    </Popover>
                    <FormMessage />
                </FormItem>
            )}
        />
    );
}
