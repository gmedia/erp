import { Helmet } from 'react-helmet-async';
import { OverdueApprovalsList } from '@/components/approval-monitoring/OverdueApprovalsList';
import { SummaryCards } from '@/components/approval-monitoring/SummaryCards';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useApprovalMonitoring } from '@/hooks/useApprovalMonitoring';
import AppLayout from '@/layouts/app-layout';

export default function ApprovalMonitoringDashboard() {
    const { data, isLoading, filters, handleFilterChange } = useApprovalMonitoring();

    return (
        <AppLayout breadcrumbs={[{ title: 'Home', href: '/' }, { title: 'Approval Monitoring', href: '#' }]}>
            <Helmet><title>Approval Monitoring</title></Helmet>
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Approval Monitoring</h2>
                        <p className="text-muted-foreground">Monitor approval workflows and overdue tasks</p>
                    </div>
                </div>

                <SummaryCards summary={data?.summary} isLoading={isLoading} />
                
                <div className="flex flex-col gap-4">
                    <div className="flex flex-wrap items-center gap-4">
                        <div className="w-full sm:w-64">
                            <Select
                                value={filters.status || 'all'}
                                onValueChange={(val) => handleFilterChange('status', val === 'all' ? undefined : val)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="in_progress">In Progress</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="rejected">Rejected</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    
                    <OverdueApprovalsList data={data?.overdue_approvals} isLoading={isLoading} />
                </div>
            </div>
        </AppLayout>
    );
}
