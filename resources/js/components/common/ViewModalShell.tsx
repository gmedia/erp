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
    headerClassName?: string;
    footerClassName?: string;
    footer?: ReactNode;
    hideFooter?: boolean;
}

export function ViewModalShell({
    open,
    onClose,
    title,
    description,
    children,
    contentClassName = 'sm:max-w-[425px]',
    headerClassName,
    footerClassName,
    footer,
    hideFooter = false,
}: Readonly<ViewModalShellProps>) {
    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent className={contentClassName}>
                <DialogHeader className={headerClassName}>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>

                {children}

                {!hideFooter && (
                    <DialogFooter className={footerClassName}>
                        {footer ?? (
                            <Button type="button" onClick={onClose}>
                                Close
                            </Button>
                        )}
                    </DialogFooter>
                )}
            </DialogContent>
        </Dialog>
    );
}
