import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { cn } from '@/lib/utils';
import {
    AlertTriangle,
    CheckCircle2,
    Info,
    XCircle,
    type LucideIcon,
} from 'lucide-react';
import type { ReactNode } from 'react';

export type PageBannerTone = 'info' | 'warning' | 'success' | 'destructive';

const TONE_CLASS: Record<PageBannerTone, string> = {
    info: 'bg-info/10 text-info',
    warning: 'bg-warning-soft text-warning',
    success: 'bg-success-soft text-success',
    destructive: 'bg-destructive-soft text-destructive-foreground',
};

const TONE_ICON: Record<PageBannerTone, LucideIcon> = {
    info: Info,
    warning: AlertTriangle,
    success: CheckCircle2,
    destructive: XCircle,
};

/**
 * Banner info/peringatan full-width di bawah header halaman
 * (docs/ui-design-rules.md §0.4): permukaan soft tanpa border, judul tebal,
 * meta/penjelasan di bawahnya, aksi opsional (tautan teks) di kanan.
 */
export function PageBanner({
    tone = 'info',
    title,
    children,
    action,
    icon,
    className,
}: Readonly<{
    tone?: PageBannerTone;
    title: ReactNode;
    children?: ReactNode;
    action?: ReactNode;
    icon?: LucideIcon;
    className?: string;
}>) {
    const Icon = icon ?? TONE_ICON[tone];

    return (
        <Alert
            data-tone={tone}
            className={cn('border-0', TONE_CLASS[tone], className)}
        >
            <Icon />
            <div className="flex flex-wrap items-start justify-between gap-x-6 gap-y-1">
                <div className="min-w-0 space-y-0.5">
                    <AlertTitle className="font-semibold">{title}</AlertTitle>
                    {children && (
                        <AlertDescription className="text-sm text-current/80">
                            {children}
                        </AlertDescription>
                    )}
                </div>
                {action && (
                    <div className="shrink-0 text-sm font-medium">{action}</div>
                )}
            </div>
        </Alert>
    );
}
