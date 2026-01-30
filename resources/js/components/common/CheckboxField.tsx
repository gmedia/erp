'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

interface CheckboxFieldProps {
    name: string;
    label: string;
    className?: string;
    description?: string;
}

export default function CheckboxField({
    name,
    label,
    className,
    description,
}: CheckboxFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem
                    className={cn(
                        'flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4 shadow',
                        className
                    )}
                >
                    <FormControl>
                        <Checkbox
                            id={name}
                            checked={field.value}
                            onCheckedChange={field.onChange}
                        />
                    </FormControl>
                    <div className="space-y-1 leading-none">
                        <FormLabel>{label}</FormLabel>
                        {description && (
                            <p className="text-sm text-muted-foreground">
                                {description}
                            </p>
                        )}
                    </div>
                    <FormMessage />
                </FormItem>
            )}
        />
    );
}
