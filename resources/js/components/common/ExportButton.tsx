'use client';

import { Button } from '@/components/ui/button';
import { Download, Loader2 } from 'lucide-react';

export function ExportButton({
    exporting,
    onClick,
    disabled,
}: {
    exporting: boolean;
    onClick: () => void;
    disabled?: boolean;
}) {
    return (
        <Button
            variant="outline"
            size="sm"
            onClick={onClick}
            disabled={disabled}
            className="border-border bg-background hover:bg-accent hover:text-accent-foreground"
        >
            {exporting ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
            ) : (
                <Download className="mr-2 h-4 w-4" />
            )}
            Export
        </Button>
    );
}
