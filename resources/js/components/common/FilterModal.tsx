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
import * as React from 'react';

type FieldDescriptor = {
    name: string;
    label: string;
    component: React.ReactNode;
};

type FilterModalProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onApply: () => void;
    onReset: () => void;
    fields: FieldDescriptor[];
};

export function FilterModal({
    open,
    onOpenChange,
    onApply,
    onReset,
    fields,
}: FilterModalProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="border-border bg-background text-foreground sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Filters</DialogTitle>
                    <DialogDescription className="text-muted-foreground">
                        Apply filters to refine the results
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    {fields.map((field) => (
                        <div key={field.name}>
                            <label className="mb-2 block text-sm font-medium">
                                {field.label}
                            </label>
                            {field.component}
                        </div>
                    ))}
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onReset}>
                        Reset
                    </Button>
                    <Button onClick={onApply}>Apply</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
