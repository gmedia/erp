import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
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
    switch (section.section_type) {
        case 'header':
            return (
                <div className="pt-2 pb-1">
                    <span className="text-sm font-semibold text-foreground">
                        {section.name}
                    </span>
                </div>
            );
        case 'separator':
            return <Separator className="my-2" />;
        case 'subtotal':
            return (
                <div className="flex items-center justify-between gap-4 border-t border-border/60 py-1.5">
                    <span className="text-sm font-medium text-muted-foreground">
                        {section.name}
                    </span>
                    <span className="text-sm font-semibold tabular-nums">
                        {formatCurrency(section.value)}
                    </span>
                </div>
            );
        case 'total':
            return (
                <div className="flex items-center justify-between gap-4 border-t-2 border-foreground/20 pt-2 pb-1">
                    <span className="text-sm font-bold">{section.name}</span>
                    <span
                        className={cn(
                            'text-sm font-bold tabular-nums',
                            section.value >= 0
                                ? 'text-emerald-700 dark:text-emerald-300'
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
                        {section.name}
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
}: Readonly<{ sections: ComputedSection[]; title?: string }>) {
    if (sections.length === 0) {
        return null;
    }

    const sorted = [...sections].sort((a, b) => a.sort_order - b.sort_order);

    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-lg">{title}</CardTitle>
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
