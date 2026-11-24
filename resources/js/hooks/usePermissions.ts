import { useAuth } from '@/contexts/auth-context';
import { useCallback, useMemo } from 'react';

/**
 * Permission pengguna saat ini, dibaca dari `employee.permissions` pada `/api/me`.
 * `can(name)` mengikuti middleware backend: nama permission harus cocok persis.
 */
export function usePermissions() {
    const { employee } = useAuth();

    const names = useMemo(
        () => new Set((employee?.permissions ?? []).map((p) => p.name)),
        [employee],
    );

    const can = useCallback((name: string) => names.has(name), [names]);

    return { can };
}
