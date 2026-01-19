import { dashboard } from '@/routes';
import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { TreeView } from '@/components/tree/tree-view';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { useEffect, useState } from 'react';
import AsyncSelectField from '@/components/common/AsyncSelectField';
import { Button } from '@/components/ui/button';
import { Zap } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';
import { Form, FormProvider, useForm } from 'react-hook-form';

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
    const [loading, setLoading] = useState(false);
    
    // Using existing FormProvider structure even if we don't have a complex schema, 
    // because AsyncSelectField expects to be inside a FormField which needs a FormProvider context via 'name' if used that way.
    // However, AsyncSelectField uses `FormField` from `shadcn` which requires `control`.
    // Let's set up a simple form.
    const form = useForm({
        defaultValues: {
            employee_id: '',
        }
    });

    const selectedEmployeeId = form.watch('employee_id');

    useEffect(() => {
        if (!selectedEmployeeId) {
            setSelectedPermissions([]);
            return;
        }

        const fetchPermissions = async () => {
            setLoading(true);
            try {
                const response = await axios.get(`/api/employees/${selectedEmployeeId}/permissions`);
                setSelectedPermissions(response.data.map(Number)); // Ensure numbers
            } catch (error) {
                console.error("Failed to fetch permissions", error);
                toast.error("Failed to fetch employee permissions.");
            } finally {
                setLoading(false);
            }
        };

        fetchPermissions();
    }, [selectedEmployeeId]);

    const handleSave = async () => {
        if (!selectedEmployeeId) return;
        
        setLoading(true);
        try {
            await axios.post(`/api/employees/${selectedEmployeeId}/permissions`, {
                permissions: selectedPermissions,
            });
            toast.success("Permissions updated successfully.");
        } catch (error) {
             console.error("Failed to update permissions", error);
             toast.error("Failed to update permissions.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permissions" />
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
                                        <AsyncSelectField
                                            name="employee_id"
                                            label="Select Employee"
                                            url="/api/employees"
                                            placeholder="Search for an employee..."
                                            labelFn={(item) => item.name}
                                            valueFn={(item) => String(item.id)}
                                        />
                                    </form>
                                </FormProvider>
                            </div>

                            {selectedEmployeeId && (
                                <div className="space-y-4">
                                     <div className="flex items-center justify-between">
                                        <h3 className="text-lg font-medium">Permissions</h3>
                                        <Button onClick={handleSave} disabled={loading}>
                                            {loading && <Zap className="mr-2 h-4 w-4 animate-spin" />}
                                            Save Changes
                                        </Button>
                                    </div>
                                    <div className="border rounded-md p-4">
                                        <TreeView 
                                            data={permissions} 
                                            selectedIds={selectedPermissions} 
                                            onSelectionChange={setSelectedPermissions}
                                        />
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
