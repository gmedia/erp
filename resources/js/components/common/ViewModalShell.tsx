'use client';

import type { ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface ViewModalShellProps {
    open: boolean;
    onClose: () => void;
    title: ReactNode;
    description: ReactNode;
    children: ReactNode;
    contentClassName?: string;
}

export function ViewModalShell({
    open,
    onClose,
    title,
    description,
    children,
    contentClassName = 'sm:max-w-[425px]',
}: Readonly<ViewModalShellProps>) {
    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className={contentClassName}>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>

                {children}

                <DialogFooter>
                    <Button type="button" onClick={onClose}>
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
