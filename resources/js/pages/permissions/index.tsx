import { EmployeeSelector } from '@/components/permissions/EmployeeSelector';
import { PermissionManager } from '@/components/permissions/PermissionManager';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useEmployeePermissions } from '@/hooks/permissions/useEmployeePermissions';
import AppLayout from '@/layouts/app-layout';
import axios from '@/lib/axios';
import { BreadcrumbItem } from '@/types';
import { Permission } from '@/types/permission';
import { useEffect, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { FormProvider, useForm } from 'react-hook-form';

export default function PermissionsIndex() {
    const [permissions, setPermissions] = useState<Permission[]>([]);

    useEffect(() => {
        axios
            .get('/api/permissions')
            .then((res) => setPermissions(res.data))
            .catch(console.error);
    }, []);
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
        },
        {
            title: 'Permissions',
            href: '/permissions',
        },
    ];

    const {
        loading,
        selectedPermissions,
        setSelectedPermissions,
        fetchPermissions,
        updatePermissions,
    } = useEmployeePermissions();

    const form = useForm({
        defaultValues: {
            employee_id: '',
        },
    });

    const selectedEmployeeId = form.watch('employee_id');

    useEffect(() => {
        if (selectedEmployeeId) {
            fetchPermissions(selectedEmployeeId);
        } else {
            setSelectedPermissions([]);
        }
    }, [selectedEmployeeId, fetchPermissions, setSelectedPermissions]);

    const handleSave = () => {
        updatePermissions(selectedEmployeeId, selectedPermissions);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>Permissions</title>
            </Helmet>
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>Permissions Hierarchy</CardTitle>
                            <CardDescription>
                                Manage system permissions for employees.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="max-w-md space-y-4">
                                <FormProvider {...form}>
                                    <form className="space-y-4">
                                        <EmployeeSelector />
                                    </form>
                                </FormProvider>
                            </div>

                            {selectedEmployeeId && (
                                <PermissionManager
                                    permissions={permissions}
                                    selectedPermissions={selectedPermissions}
                                    onSelectionChange={setSelectedPermissions}
                                    onSave={handleSave}
                                    loading={loading}
                                />
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
