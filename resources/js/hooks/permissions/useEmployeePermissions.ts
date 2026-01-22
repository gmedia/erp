import axios from 'axios';
import { useCallback, useState } from 'react';
import { toast } from 'sonner';

export const useEmployeePermissions = () => {
    const [loading, setLoading] = useState(false);
    const [selectedPermissions, setSelectedPermissions] = useState<number[]>(
        [],
    );

    const fetchPermissions = useCallback(async (employeeId: string) => {
        if (!employeeId) {
            setSelectedPermissions([]);
            return;
        }

        setLoading(true);
        try {
            const response = await axios.get(
                `/api/employees/${employeeId}/permissions`,
            );
            setSelectedPermissions(response.data.map(Number));
        } catch (error) {
            console.error('Failed to fetch permissions', error);
            toast.error('Failed to fetch employee permissions.');
        } finally {
            setLoading(false);
        }
    }, []);

    const updatePermissions = useCallback(
        async (employeeId: string, permissions: number[]) => {
            if (!employeeId) return;

            setLoading(true);
            try {
                await axios.post(`/api/employees/${employeeId}/permissions`, {
                    permissions: permissions,
                });
                toast.success('Permissions updated successfully.');
            } catch (error) {
                console.error('Failed to update permissions', error);
                toast.error('Failed to update permissions.');
            } finally {
                setLoading(false);
            }
        },
        [],
    );

    return {
        loading,
        selectedPermissions,
        setSelectedPermissions,
        fetchPermissions,
        updatePermissions,
    };
};
