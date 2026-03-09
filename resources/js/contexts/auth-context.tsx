import axios from '@/lib/axios';
import { type User } from '@/types/user';
import React, { createContext, useContext, useEffect, useState } from 'react';

interface Employee {
    id: number;
    name: string;
    permissions?: any[];
}

interface MenuItem {
    id: number;
    parent_id: number | null;
    name: string;
    display_name: string;
    url: string | null;
    icon: string;
    order: number;
    children?: MenuItem[];
}

interface AuthContextType {
    user: User | null;
    employee: Employee | null;
    menus: MenuItem[];
    companyName: string;
    companyLogoUrl: string | null;
    translations: Record<string, string>;
    locale: string;
    pendingApprovalsCount: number;
    isLoading: boolean;
    login: (token: string, userData: any) => void;
    logout: () => Promise<void>;
    refreshAuth: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType>({
    user: null,
    employee: null,
    menus: [],
    companyName: 'Laravel',
    companyLogoUrl: null,
    translations: {},
    locale: 'en',
    pendingApprovalsCount: 0,
    isLoading: true,
    login: () => {},
    logout: async () => {},
    refreshAuth: async () => {},
});

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({
    children,
}) => {
    const [user, setUser] = useState<User | null>(null);
    const [employee, setEmployee] = useState<Employee | null>(null);
    const [menus, setMenus] = useState<MenuItem[]>([]);
    const [companyName, setCompanyName] = useState<string>('Laravel');
    const [companyLogoUrl, setCompanyLogoUrl] = useState<string | null>(null);
    const [translations, setTranslations] = useState<Record<string, string>>(
        {},
    );
    const [locale, setLocale] = useState<string>('en');
    const [pendingApprovalsCount, setPendingApprovalsCount] =
        useState<number>(0);
    const [isLoading, setIsLoading] = useState<boolean>(true);

    const refreshAuth = async () => {
        const token = localStorage.getItem('api_token');
        if (!token) {
            setIsLoading(false);
            return;
        }

        try {
            const { data } = await axios.get('/api/me');
            setUser(data.user);
            setEmployee(data.employee);
            setMenus(data.menus || []);
            setCompanyName(data.companyName);
            setCompanyLogoUrl(data.companyLogoUrl);
            setTranslations(data.translations || {});
            setLocale(data.locale || 'en');
            setPendingApprovalsCount(data.pendingApprovalsCount || 0);

            // Make translations available globally if needed by unhandled context cases
            (window as any).__APP_COMPANY_NAME__ = data.companyName;
        } catch (error) {
            console.error('Failed to fetch auth state', error);
            localStorage.removeItem('api_token');
            setUser(null);
        } finally {
            setIsLoading(false);
        }
    };

    useEffect(() => {
        refreshAuth();
    }, []);

    const login = (token: string, data: any) => {
        localStorage.setItem('api_token', token);
        if (data.user) setUser(data.user);
        refreshAuth();
    };

    const logout = async () => {
        try {
            await axios.post('/api/logout');
        } catch (e) {
            console.error(e);
        }
        localStorage.removeItem('api_token');
        setUser(null);
        window.location.href = '/login';
    };

    return (
        <AuthContext.Provider
            value={{
                user,
                employee,
                menus,
                companyName,
                companyLogoUrl,
                translations,
                locale,
                pendingApprovalsCount,
                isLoading,
                login,
                logout,
                refreshAuth,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => useContext(AuthContext);
