import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { AlertCircle, CheckCircle2, X } from 'lucide-react';

export interface ImportRowError {
    row: number;
    field: string;
    message: string;
}

export interface ImportResultData {
    imported: number;
    skipped: number;
    errors: ImportRowError[];
}

interface ImportResultSummaryProps {
    readonly result: ImportResultData;
    readonly className?: string;
}

/**
 * Ringkasan hasil import (Imported / Skipped / Errors) + tabel error per baris.
 * Dipakai bersama oleh ImportDialog dan dialog import khusus modul.
 */
export function ImportResultSummary({
    result,
    className,
}: ImportResultSummaryProps) {
    return (
        <div
            className={cn('space-y-3 rounded-md border p-3 text-sm', className)}
        >
            <div className="flex items-center gap-4 font-medium">
                <div className="flex items-center text-success">
                    <CheckCircle2 className="mr-1.5 size-4" />
                    Imported: {result.imported}
                </div>
                <div className="flex items-center text-warning">
                    <AlertCircle className="mr-1.5 size-4" />
                    Skipped: {result.skipped}
                </div>
                <div className="flex items-center text-destructive-foreground">
                    <X className="mr-1.5 size-4" />
                    Errors: {result.errors.length}
                </div>
            </div>

            {result.errors.length > 0 && (
                <ScrollArea className="max-h-40 rounded bg-muted">
                    <div className="p-2 pr-4 text-xs">
                        <table className="w-full text-left">
                            <thead>
                                <tr>
                                    <th className="pb-1 text-muted-foreground">
                                        Row
                                    </th>
                                    <th className="pb-1 text-muted-foreground">
                                        Field
                                    </th>
                                    <th className="pb-1 text-muted-foreground">
                                        Message
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {result.errors.map((err) => (
                                    <tr
                                        key={`${err.row}-${err.field}-${err.message}`}
                                        className="border-t border-border"
                                    >
                                        <td className="py-1 align-top font-mono text-muted-foreground">
                                            {err.row}
                                        </td>
                                        <td className="py-1 align-top font-medium text-foreground">
                                            {err.field}
                                        </td>
                                        <td className="py-1 align-top text-destructive-foreground">
                                            {err.message}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </ScrollArea>
            )}
        </div>
    );
}
