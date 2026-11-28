import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/contexts/i18n-context';
import { cn, formatCurrency } from '@/lib/utils';

export interface ComputedSection {
    code: string;
    name: string;
    section_type: 'header' | 'separator' | 'line_item' | 'subtotal' | 'total';
    value: number;
    formula: string | null;
    sort_order: number;
}

function ComputedSectionRow({
    section,
}: Readonly<{ section: ComputedSection }>) {
    const { t } = useTranslation();
    const key = `reports.sections.${section.code}`;
    const translated = t(key);
    const label = translated === key ? section.name : translated;

    switch (section.section_type) {
        case 'header':
            return (
                <div className="pt-2 pb-1">
                    <span className="text-sm font-semibold text-foreground">
                        {label}
                    </span>
                </div>
            );
        case 'separator':
            return <Separator className="my-2" />;
        case 'subtotal':
            return (
                <div className="flex items-center justify-between gap-4 border-t border-border/60 py-1.5">
                    <span className="text-sm font-medium text-muted-foreground">
                        {label}
                    </span>
                    <span className="text-sm font-semibold tabular-nums">
                        {formatCurrency(section.value)}
                    </span>
                </div>
            );
        case 'total':
            return (
                <div className="flex items-center justify-between gap-4 border-t-2 border-foreground/20 pt-2 pb-1">
                    <span className="text-sm font-bold">{label}</span>
                    <span
                        className={cn(
                            'text-sm font-bold tabular-nums',
                            section.value >= 0
                                ? 'text-success'
                                : 'text-destructive',
                        )}
                    >
                        {formatCurrency(section.value)}
                    </span>
                </div>
            );
        default:
            return (
                <div className="flex items-center justify-between gap-4 py-1">
                    <span className="text-sm text-muted-foreground">
                        {label}
                    </span>
                    <span className="text-sm tabular-nums">
                        {formatCurrency(section.value)}
                    </span>
                </div>
            );
    }
}

export function ComputedSectionsCard({
    sections,
    title = 'Computed Sections',
    titleKey,
}: Readonly<{ sections: ComputedSection[]; title?: string; titleKey?: string }>) {
    const { t } = useTranslation();

    if (sections.length === 0) {
        return null;
    }

    const sorted = [...sections].sort((a, b) => a.sort_order - b.sort_order);
    const cardTitle = titleKey && t(titleKey) !== titleKey ? t(titleKey) : title;

    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-lg">{cardTitle}</CardTitle>
            </CardHeader>
            <CardContent>
                <div className="rounded-lg border bg-background p-4">
                    {sorted.map((section) => (
                        <ComputedSectionRow
                            key={section.code}
                            section={section}
                        />
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
