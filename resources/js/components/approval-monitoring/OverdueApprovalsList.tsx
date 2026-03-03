import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { type OverdueApproval } from '@/hooks/useApprovalMonitoring';
import { format } from 'date-fns';
import { AlertCircle } from 'lucide-react';

interface OverdueApprovalsListProps {
    data?: OverdueApproval[];
    isLoading: boolean;
}

export function OverdueApprovalsList({ data, isLoading }: OverdueApprovalsListProps) {
    if (isLoading) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Overdue Approvals</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex h-32 items-center justify-center text-muted-foreground">
                        Loading data...
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (!data || data.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Overdue Approvals</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-col items-center justify-center space-y-2 rounded-md border border-dashed p-8 text-center">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900">
                            <AlertCircle className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <h3 className="font-semibold text-emerald-700 dark:text-emerald-400">All caught up!</h3>
                        <p className="text-sm text-muted-foreground">No approvals are currently overdue.</p>
                    </div>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="col-span-1 border-rose-200 dark:border-rose-900/50">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-rose-600 dark:text-rose-400">
                    <AlertCircle className="h-5 w-5" />
                    Overdue Approvals
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Document Type</TableHead>
                                <TableHead>Document Name</TableHead>
                                <TableHead>Submitter</TableHead>
                                <TableHead>Pending Step</TableHead>
                                <TableHead>Due Date</TableHead>
                                <TableHead className="text-right">Hours Overdue</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">{item.document_type}</TableCell>
                                    <TableCell>{item.document_name}</TableCell>
                                    <TableCell>{item.submitter_name}</TableCell>
                                    <TableCell>{item.step_name}</TableCell>
                                    <TableCell>
                                        <div className="flex flex-col">
                                            <span>{format(new Date(item.due_at), 'MMM dd, yyyy')}</span>
                                            <span className="text-xs text-muted-foreground">{format(new Date(item.due_at), 'HH:mm')}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right text-rose-600 font-medium">
                                        {Math.floor(item.hours_overdue)}h
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}
