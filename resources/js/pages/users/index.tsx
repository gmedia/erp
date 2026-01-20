import { dashboard } from '@/routes';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { BreadcrumbItem } from '@/types';
import { useEffect, useState } from 'react';
import AsyncSelectField from '@/components/common/AsyncSelectField';
import { Button } from '@/components/ui/button';
import { Zap } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';
import { FormProvider, useForm } from 'react-hook-form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface UserFormData {
    employee_id: string;
    name: string;
    email: string;
    password: string;
}

interface ValidationErrors {
    name?: string[];
    email?: string[];
    password?: string[];
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

    const [loading, setLoading] = useState(false);
    const [userExists, setUserExists] = useState(false);
    const [errors, setErrors] = useState<ValidationErrors>({});

    const form = useForm<UserFormData>({
        defaultValues: {
            employee_id: '',
            name: '',
            email: '',
            password: '',
        },
    });

    const selectedEmployeeId = form.watch('employee_id');

    useEffect(() => {
        if (!selectedEmployeeId) {
            form.reset({
                employee_id: '',
                name: '',
                email: '',
                password: '',
            });
            setUserExists(false);
            setErrors({});
            return;
        }

        const fetchUserData = async () => {
            setLoading(true);
            setErrors({});
            try {
                const response = await axios.get(`/api/employees/${selectedEmployeeId}/user`);
                const { user, employee } = response.data;

                setUserExists(!!user);
                form.setValue('name', user?.name ?? employee.name);
                form.setValue('email', user?.email ?? employee.email);
                form.setValue('password', '');
            } catch (error) {
                console.error('Failed to fetch user data', error);
                toast.error('Failed to fetch user data.');
            } finally {
                setLoading(false);
            }
        };

        fetchUserData();
    }, [selectedEmployeeId]);

    const handleSave = async () => {
        if (!selectedEmployeeId) return;

        setLoading(true);
        setErrors({});
        try {
            const password = form.getValues('password');
            await axios.post(`/api/employees/${selectedEmployeeId}/user`, {
                name: form.getValues('name'),
                email: form.getValues('email'),
                password: password || undefined,
            });
            toast.success('User saved successfully.');
            setUserExists(true);
        } catch (error: any) {
            console.error('Failed to save user', error);
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setErrors(error.response.data.errors);
                toast.error('Please fix the validation errors.');
            } else if (error.response?.data?.message) {
                toast.error(error.response.data.message);
            } else {
                toast.error('Failed to save user.');
            }
        } finally {
            setLoading(false);
        }
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
                                <div className="max-w-md space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">User Name</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            {...form.register('name')}
                                            placeholder="Enter user name"
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-destructive">{errors.name[0]}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="email">User Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            {...form.register('email')}
                                            placeholder="Enter user email"
                                        />
                                        {errors.email && (
                                            <p className="text-sm text-destructive">{errors.email[0]}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="password">
                                            User Password {!userExists && <span className="text-destructive">*</span>}
                                        </Label>
                                        <Input
                                            id="password"
                                            type="password"
                                            {...form.register('password')}
                                            placeholder={userExists ? 'Leave empty to keep current password' : 'Enter password (required for new user)'}
                                        />
                                        {errors.password && (
                                            <p className="text-sm text-destructive">{errors.password[0]}</p>
                                        )}
                                    </div>

                                    <Button onClick={handleSave} disabled={loading} className="w-full">
                                        {loading && <Zap className="mr-2 h-4 w-4 animate-spin" />}
                                        Save Changes
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

