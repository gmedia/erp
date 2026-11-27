'use client';

import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    labelFallbackText,
    LabelText,
    TranslatedText,
} from '@/components/common/TranslatedText';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select } from '@/components/ui/select';
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
}: Readonly<FilterModalProps>) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[90vh] flex-col overflow-hidden border-border bg-background p-0 text-foreground sm:max-w-[425px]">
                <div className="shrink-0 p-6 pb-2">
                    <DialogHeader>
                        <DialogTitle>
                            <TranslatedText
                                k="common.filter_modal.title"
                                fallback="Filters"
                            />
                        </DialogTitle>
                        <DialogDescription className="text-muted-foreground">
                            <TranslatedText
                                k="common.filter_modal.description"
                                fallback="Apply filters to refine the results"
                            />
                        </DialogDescription>
                    </DialogHeader>
                </div>
                <ScrollArea className="flex-1 px-6">
                    <div className="grid gap-4 py-4 pr-6">
                        {filterFields.map((field) => {
                            const element = React.isValidElement(
                                field.component,
                            )
                                ? field.component
                                : null;
                            const isSelect =
                                element &&
                                (element.type === Select ||
                                    (element.type as { displayName?: string })
                                        ?.displayName === 'Select' ||
                                    (element.type as { displayName?: string })
                                        ?.displayName === 'AsyncSelect');

                            const isDatePicker =
                                element?.type === FilterDatePicker;

                            const commonProps = {
                                name: field.name,
                                value: tempFilters[field.name] ?? '', // Always pass a defined string to prevent uncontrolled-to-controlled warning
                                ...(isDatePicker
                                    ? {}
                                    : {
                                          label: labelFallbackText(field.label),
                                      }),
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
                                          ? {
                                                onValueChange:
                                                    onValueChangeHandler,
                                            }
                                          : { onChange: onChangeHandler }),
                                  })
                                : null;

                            return (
                                <div key={field.name}>
                                    <label className="mb-2 block text-sm font-medium">
                                        <LabelText label={field.label} />
                                    </label>
                                    {componentWithProps}
                                </div>
                            );
                        })}
                    </div>
                </ScrollArea>
                <div className="shrink-0 p-6 pt-2">
                    <DialogFooter>
                        <Button variant="outline" onClick={onReset}>
                            <TranslatedText
                                k="common.filter_modal.reset"
                                fallback="Reset"
                            />
                        </Button>
                        <Button variant="outline" onClick={onClearAll}>
                            <TranslatedText
                                k="common.filter_modal.clear_all"
                                fallback="Clear All"
                            />
                        </Button>
                        <Button onClick={onApply}>
                            <TranslatedText
                                k="common.filter_modal.apply"
                                fallback="Apply Filters"
                            />
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
}
