import { dashboard } from '@/routes';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { TreeView } from '@/components/tree/tree-view';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { useState } from 'react';

interface Permission {
    id: number;
    name: string;
    display_name: string;
    parent_id: number | null;
}

interface Props {
    permissions: Permission[];
}

export default function PermissionsIndex({ permissions }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: dashboard().url,
        },
        {
            title: 'Permissions',
            href: '/permissions',
        },
    ];

    const [selectedPermissions, setSelectedPermissions] = useState<number[]>([]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permissions" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>Permissions Hierarchy</CardTitle>
                            <CardDescription>
                                View and manage system permissions.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <TreeView 
                                data={permissions} 
                                selectedIds={selectedPermissions} 
                                onSelectionChange={setSelectedPermissions}
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
