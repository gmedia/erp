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
                        'flex flex-row items-start gap-4 space-y-0 rounded-lg border p-4 shadow-sm transition-all hover:bg-accent/50 has-[[data-state=checked]]:border-primary/50 has-[[data-state=checked]]:bg-primary/5',
                        className
                    )}
                >
                    <FormControl>
                        <Checkbox
                            id={name}
                            checked={field.value}
                            onCheckedChange={field.onChange}
                            className="mt-1"
                        />
                    </FormControl>
                    <div className="grid gap-1.5 leading-none">
                        <FormLabel className="cursor-pointer font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                            {label}
                        </FormLabel>
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
