import { dashboard } from '@/routes';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { useEffect } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { EmployeeSelector } from '@/components/permissions/EmployeeSelector'; // Reuse EmployeeSelector
import { useEmployeeUser } from '@/hooks/users/useEmployeeUser';
import { UserForm } from '@/components/users/UserForm';

interface UserFormData {
    employee_id: string;
    name: string;
    email: string;
    password: string;
}

export default function UsersIndex() {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: dashboard().url,
        },
        {
            title: 'Users',
            href: '/users',
        },
    ];

    const form = useForm<UserFormData>({
        defaultValues: {
            employee_id: '',
            name: '',
            email: '',
            password: '',
        },
    });

    const { loading, userExists, errors, fetchUser, saveUser } = useEmployeeUser(form);
    const selectedEmployeeId = form.watch('employee_id');

    useEffect(() => {
        fetchUser(selectedEmployeeId);
    }, [selectedEmployeeId, fetchUser]);

    const handleSave = () => {
        saveUser(selectedEmployeeId, form.getValues());
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>User Management</CardTitle>
                            <CardDescription>
                                Manage user accounts linked to employees.
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
                                <FormProvider {...form}>
                                    <UserForm 
                                        loading={loading} 
                                        userExists={userExists} 
                                        errors={errors} 
                                        onSave={handleSave} 
                                    />
                                </FormProvider>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

