import { OverdueApprovalsList } from '@/components/approval-monitoring/OverdueApprovalsList';
import { SummaryCards } from '@/components/approval-monitoring/SummaryCards';
import { AsyncSelect } from '@/components/common/AsyncSelect';
import { PageHeader } from '@/components/common/PageHeader';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useApprovalMonitoring } from '@/hooks/useApprovalMonitoring';
import AppLayout from '@/layouts/app-layout';
import { Helmet } from 'react-helmet-async';

export default function ApprovalMonitoringDashboard() {
    const { data, isLoading, filters, handleFilterChange } =
        useApprovalMonitoring();

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Approval Monitoring', href: '/approval-monitoring' },
            ]}
        >
            <Helmet>
                <title>Approval Monitoring</title>
            </Helmet>
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <PageHeader
                    title="Approval Monitoring"
                    description="Monitor approval workflows and overdue tasks."
                />

                <SummaryCards summary={data?.summary} isLoading={isLoading} />

                <div className="flex flex-col gap-4">
                    <div className="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                        <div className="w-full space-y-2 sm:w-64">
                            <Label
                                htmlFor="status-filter"
                                className="text-sm font-medium text-muted-foreground"
                            >
                                Status
                            </Label>
                            <Select
                                value={filters.status || 'all'}
                                onValueChange={(val) =>
                                    handleFilterChange(
                                        'status',
                                        val === 'all' ? undefined : val,
                                    )
                                }
                            >
                                <SelectTrigger id="status-filter">
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All Statuses
                                    </SelectItem>
                                    <SelectItem value="pending">
                                        Pending
                                    </SelectItem>
                                    <SelectItem value="in_progress">
                                        In Progress
                                    </SelectItem>
                                    <SelectItem value="approved">
                                        Approved
                                    </SelectItem>
                                    <SelectItem value="rejected">
                                        Rejected
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="w-full space-y-2 sm:w-64">
                            <Label className="text-sm font-medium text-muted-foreground">
                                Branch
                            </Label>
                            <AsyncSelect
                                url="/api/branches"
                                label="Branch"
                                placeholder="All Branches"
                                value={filters.branch_id}
                                onValueChange={(val) =>
                                    handleFilterChange(
                                        'branch_id',
                                        val || undefined,
                                    )
                                }
                            />
                        </div>
                    </div>

                    <OverdueApprovalsList
                        data={data?.overdue_approvals}
                        isLoading={isLoading}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
