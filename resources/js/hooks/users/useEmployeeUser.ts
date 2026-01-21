import { useState, useCallback } from 'react';
import axios from 'axios';
import { toast } from 'sonner';
import { UseFormReturn } from 'react-hook-form';

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

export const useEmployeeUser = (form: UseFormReturn<UserFormData>) => {
    const [loading, setLoading] = useState(false);
    const [userExists, setUserExists] = useState(false);
    const [errors, setErrors] = useState<ValidationErrors>({});

    const fetchUser = useCallback(async (employeeId: string) => {
        if (!employeeId) {
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

        setLoading(true);
        setErrors({});
        try {
            const response = await axios.get(`/api/employees/${employeeId}/user`);
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
    }, [form]);

    const saveUser = useCallback(async (employeeId: string, data: UserFormData) => {
        if (!employeeId) return;

        setLoading(true);
        setErrors({});
        try {
            await axios.post(`/api/employees/${employeeId}/user`, {
                name: data.name,
                email: data.email,
                password: data.password || undefined,
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
    }, []);

    return {
        loading,
        userExists,
        errors,
        fetchUser,
        saveUser
    };
};
