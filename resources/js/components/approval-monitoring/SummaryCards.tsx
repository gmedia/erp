import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type ApprovalSummary } from '@/hooks/useApprovalMonitoring';
import { Activity, CheckCircle2, Clock, X } from 'lucide-react';

interface SummaryCardsProps {
    summary?: ApprovalSummary;
    isLoading: boolean;
}

export function SummaryCards({
    summary,
    isLoading,
}: Readonly<SummaryCardsProps>) {
    return (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        Pending Approvals
                    </CardTitle>
                    <Clock className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold">
                        {isLoading ? '...' : summary?.total_pending || 0}
                    </div>
                    <p className="text-xs text-muted-foreground">
                        Requests waiting for action
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        Approved Today
                    </CardTitle>
                    <CheckCircle2 className="h-4 w-4 text-success" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-success">
                        {isLoading ? '...' : summary?.approved_today || 0}
                    </div>
                    <p className="text-xs text-muted-foreground">
                        Completed positive actions today
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        Rejected Today
                    </CardTitle>
                    <X className="h-4 w-4 text-destructive-foreground" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-destructive-foreground">
                        {isLoading ? '...' : summary?.rejected_today || 0}
                    </div>
                    <p className="text-xs text-muted-foreground">
                        Completed negative actions today
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                        Avg Processing Time
                    </CardTitle>
                    <Activity className="h-4 w-4 text-info" />
                </CardHeader>
                <CardContent>
                    <div className="text-2xl font-bold text-info">
                        {isLoading
                            ? '...'
                            : `${summary?.avg_processing_time_hours || 0}h`}
                    </div>
                    <p className="text-xs text-muted-foreground">
                        For completed approvals
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
