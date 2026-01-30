'use client';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Select } from '@/components/ui/select';
import { ScrollArea } from '@/components/ui/scroll-area';
import * as React from 'react';
import type { FieldDescriptor } from './filters';

interface FilterModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    filterFields: FieldDescriptor[];
    tempFilters: Record<string, string>;
    onTempFiltersChange: (filters: Record<string, string>) => void;
    onApply: () => void;
    onReset: () => void;
    onClearAll: () => void;
}

/**
 * FilterModal - A modal dialog for advanced filtering
 *
 * Handles dynamic filter fields and temporary filter state management.
 */
export function FilterModal({
    open,
    onOpenChange,
    filterFields,
    tempFilters,
    onTempFiltersChange,
    onApply,
    onReset,
    onClearAll,
}: FilterModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="border-border bg-background text-foreground sm:max-w-[425px] max-h-[90vh] p-0 overflow-hidden flex flex-col">
                <div className="shrink-0 p-6 pb-2">
                    <DialogHeader>
                        <DialogTitle>Filters</DialogTitle>
                        <DialogDescription className="text-muted-foreground">
                            Apply filters to refine the results
                        </DialogDescription>
                    </DialogHeader>
                </div>
                <div className="flex-1 overflow-y-auto px-6">
                    <div className="grid gap-4 py-4 pr-6">
                    {filterFields.map((field) => {
                        const element = React.isValidElement(field.component)
                            ? field.component
                            : null;
                        const isSelect =
                            element &&
                            (element.type === Select ||
                                (element.type as { displayName?: string })
                                    ?.displayName === 'Select' ||
                                (element.type as { displayName?: string })
                                    ?.displayName === 'AsyncSelect');

                        const commonProps = {
                            value: tempFilters[field.name] ?? '',
                            placeholder:
                                (element?.props as { placeholder?: string })
                                    ?.placeholder ?? '',
                        };

                        const onChangeHandler = (
                            e: React.ChangeEvent<HTMLInputElement>,
                        ) => {
                            onTempFiltersChange({
                                ...tempFilters,
                                [field.name]: e.target.value,
                            });
                        };

                        const onValueChangeHandler = (value: string) => {
                            onTempFiltersChange({
                                ...tempFilters,
                                [field.name]: value,
                            });
                        };

                        const componentWithProps = element
                            ? React.cloneElement(element, {
                                  ...commonProps,
                                  ...(isSelect
                                      ? { onValueChange: onValueChangeHandler }
                                      : { onChange: onChangeHandler }),
                              })
                            : null;

                        return (
                            <div key={field.name}>
                                <label className="mb-2 block text-sm font-medium">
                                    {field.label}
                                </label>
                                {componentWithProps}
                            </div>
                        );
                    })}
                </div>
            </div>
                <div className="shrink-0 p-6 pt-2">
                    <DialogFooter>
                        <Button variant="outline" onClick={onReset}>
                            Reset
                        </Button>
                        <Button variant="outline" onClick={onClearAll}>
                            Clear All
                        </Button>
                        <Button onClick={onApply}>Apply Filters</Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
}
