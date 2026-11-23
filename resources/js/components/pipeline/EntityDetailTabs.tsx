import { ApprovalHistoryTimeline } from '@/components/approvals/ApprovalHistoryTimeline';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useTranslation } from '@/contexts/i18n-context';

import { EntityStateTimeline } from './EntityStateTimeline';

interface EntityDetailTabsProps {
    entityType: string;
    entityId: number;
    /** Berubah setelah status diganti; memaksa timeline dimuat ulang. */
    timelineKey: number;
    /** Konten tab Details (header fields dan tabel item) milik modal pemanggil. */
    children: React.ReactNode;
}

/**
 * Tab Details / Timeline / Approvals untuk ViewModal entity pipeline.
 * Dipakai bersama oleh modal PO dan PR.
 */
export function EntityDetailTabs({
    entityType,
    entityId,
    timelineKey,
    children,
}: Readonly<EntityDetailTabsProps>) {
    const { t } = useTranslation();

    return (
        <Tabs defaultValue="details" className="min-h-0 flex-1">
            <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="details">
                    {t('approval.tab_details')}
                </TabsTrigger>
                <TabsTrigger value="timeline">
                    {t('approval.tab_timeline')}
                </TabsTrigger>
                <TabsTrigger value="approvals">
                    {t('approval.tab_approvals')}
                </TabsTrigger>
            </TabsList>
            <TabsContent
                value="timeline"
                className="mt-3 max-h-[60vh] overflow-y-auto"
            >
                <EntityStateTimeline
                    key={timelineKey}
                    entityType={entityType}
                    entityId={entityId}
                />
            </TabsContent>
            <TabsContent
                value="approvals"
                className="mt-3 max-h-[60vh] overflow-y-auto"
            >
                <ApprovalHistoryTimeline
                    entityType={entityType}
                    entityId={entityId}
                />
            </TabsContent>
            <TabsContent value="details" className="min-h-0 flex-1">
                {children}
            </TabsContent>
        </Tabs>
    );
}
