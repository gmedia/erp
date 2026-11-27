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

import {
    LabelText,
    type TranslatableLabel,
    useTranslatableText,
} from './TranslatedText';

interface DatePickerFieldProps {
    name: string;
    label: TranslatableLabel;
    placeholder?: TranslatableLabel;
    disabled?: (date: Date) => boolean;
    locked?: boolean;
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
    locked = false,
    className,
}: Readonly<DatePickerFieldProps>) {
    const translatedPlaceholder = useTranslatableText(placeholder);
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
                        <FormLabel>
                            <LabelText label={label} />
                        </FormLabel>
                        <Popover>
                            <PopoverTrigger asChild>
                                <FormControl>
                                    <Button
                                        variant="outline"
                                        disabled={locked}
                                        className={cn(
                                            'h-8 w-full rounded-[6px] border border-transparent bg-secondary shadow-none px-2.5 text-left text-sm font-normal hover:bg-secondary focus-visible:ring-2 focus-visible:ring-[2px] focus-visible:ring-ring/40',
                                            !field.value &&
                                                'text-muted-foreground',
                                        )}
                                    >
                                        {field.value ? (
                                            formatDateByRegionalSettings(
                                                new Date(field.value),
                                            )
                                        ) : (
                                            <span>{translatedPlaceholder}</span>
                                        )}
                                        <CalendarIcon className="ml-auto size-3.5 text-muted-foreground" />
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
