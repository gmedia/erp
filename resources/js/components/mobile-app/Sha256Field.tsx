import { Button } from '@/components/ui/button';
import { useTranslation } from '@/contexts/i18n-context';
import { useClipboard } from '@/hooks/use-clipboard';
import { cn } from '@/lib/utils';
import { Check, Copy } from 'lucide-react';
import { toast } from 'sonner';

interface Sha256FieldProps {
    value: string;
    compact?: boolean;
    className?: string;
    testIdPrefix?: string;
}

function shortenHash(value: string): string {
    if (value.length <= 20) return value;

    return `${value.slice(0, 12)}…${value.slice(-6)}`;
}

export function Sha256Field({
    value,
    compact = false,
    className,
    testIdPrefix = 'sha256',
}: Readonly<Sha256FieldProps>) {
    const { t } = useTranslation();
    const [copiedText, copy] = useClipboard();
    const copied = copiedText === value;

    const handleCopy = async () => {
        const ok = await copy(value);
        if (ok) {
            toast.success(t('mobileApp.sha256_copied'));
        } else {
            toast.error(t('mobileApp.sha256_copy_failed'));
        }
    };

    return (
        <div className={cn('flex min-w-0 items-center gap-1.5', className)}>
            <code
                className={cn(
                    'min-w-0 font-mono text-xs text-muted-foreground',
                    compact ? 'truncate' : 'break-all',
                )}
                title={value}
                data-testid={`${testIdPrefix}-value`}
            >
                {compact ? shortenHash(value) : value}
            </code>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                className="size-6 shrink-0"
                aria-label={t('mobileApp.copy_sha256')}
                onClick={() => void handleCopy()}
                data-testid={`${testIdPrefix}-copy`}
            >
                {copied ? (
                    <Check
                        className="size-3.5 text-success"
                        aria-hidden="true"
                    />
                ) : (
                    <Copy className="size-3.5" aria-hidden="true" />
                )}
            </Button>
        </div>
    );
}
