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
import { cn } from '@/lib/utils';

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
    contentClassName,
    headerClassName,
    footerClassName,
    footer,
    hideFooter = false,
}: Readonly<ViewModalShellProps>) {
    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <DialogContent
                className={cn(
                    'gap-3 p-4 sm:max-w-lg',
                    contentClassName,
                )}
            >
                <DialogHeader className={cn('gap-1 pr-8', headerClassName)}>
                    <DialogTitle className="text-base leading-tight">
                        {title}
                    </DialogTitle>
                    <DialogDescription className="text-xs">
                        {description}
                    </DialogDescription>
                </DialogHeader>

                {children}

                {!hideFooter && (
                    <DialogFooter className={cn('pt-1', footerClassName)}>
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
