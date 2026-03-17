import axiosInstance from '@/lib/axios';
import { type Translations } from '@/types/i18n';
import { type Permission } from '@/types/permission';
import { type User } from '@/types/user';
import {
    getRegionalDateFormatSettings,
    setRegionalDateFormatSettings,
    type RegionalDateFormatSettings,
} from '@/utils/date-format';
import {
    getRegionalNumberFormatSettings,
    setRegionalNumberFormatSettings,
    type RegionalNumberFormatSettings,
} from '@/utils/number-format';
import React, {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
} from 'react';

interface Employee {
    id: number;
    name: string;
    permissions?: Permission[];
    email?: string;
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

interface AuthMeResponse {
    user: User;
    employee: Employee | null;
    menus?: MenuItem[];
    companyName: string;
    companyLogoUrl: string | null;
    translations?: Translations | Record<string, never>;
    locale?: string;
    pendingApprovalsCount?: number;
    regionalSettings?: Partial<
        RegionalNumberFormatSettings & RegionalDateFormatSettings
    >;
}

type RegionalSettings =
    RegionalNumberFormatSettings & RegionalDateFormatSettings;

interface AuthContextType {
    user: User | null;
    employee: Employee | null;
    menus: MenuItem[];
    companyName: string;
    companyLogoUrl: string | null;
    translations: Translations | Record<string, never>;
    locale: string;
    regionalSettings: RegionalSettings;
    pendingApprovalsCount: number;
    isLoading: boolean;
    login: (
        token: string,
        userData: { user: User; employee: Employee },
    ) => void;
    logout: () => Promise<void>;
    refreshAuth: () => Promise<void>;
}

const defaultRegionalSettings: RegionalSettings = {
    ...getRegionalNumberFormatSettings(),
    ...getRegionalDateFormatSettings(),
};

const AuthContext = createContext<AuthContextType>({
    user: null,
    employee: null,
    menus: [],
    companyName: 'Laravel',
    companyLogoUrl: null,
    translations: {},
    locale: 'en',
    regionalSettings: defaultRegionalSettings,
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
    const [translations, setTranslations] = useState<
        Translations | Record<string, never>
    >({});
    const [locale, setLocale] = useState<string>('en');
    const [regionalSettings, setRegionalSettings] =
        useState<RegionalSettings>(defaultRegionalSettings);
    const [pendingApprovalsCount, setPendingApprovalsCount] =
        useState<number>(0);
    const [isLoading, setIsLoading] = useState<boolean>(true);

    const refreshAuth = useCallback(async () => {
        const token = localStorage.getItem('api_token');
        if (!token) {
            setIsLoading(false);
            return;
        }

        try {
            const { data } = await axiosInstance.get<AuthMeResponse>('/api/me');
            const syncedRegionalNumberSettings = setRegionalNumberFormatSettings(
                data.regionalSettings ?? {},
            );
            const syncedRegionalDateSettings = setRegionalDateFormatSettings(
                data.regionalSettings ?? {},
            );
            const syncedRegionalSettings: RegionalSettings = {
                ...syncedRegionalNumberSettings,
                ...syncedRegionalDateSettings,
            };

            setUser(data.user);
            setEmployee(data.employee);
            setMenus(data.menus || []);
            setCompanyName(data.companyName);
            setCompanyLogoUrl(data.companyLogoUrl);
            setTranslations(data.translations || {});
            setLocale(data.locale || 'en');
            setRegionalSettings(syncedRegionalSettings);
            setPendingApprovalsCount(data.pendingApprovalsCount || 0);

            // Make translations available globally if needed by unhandled context cases
            (
                globalThis as typeof globalThis & {
                    __APP_COMPANY_NAME__?: string;
                }
            ).__APP_COMPANY_NAME__ = data.companyName;
        } catch (error) {
            console.error('Failed to fetch auth state', error);
            localStorage.removeItem('api_token');
            setUser(null);
        } finally {
            setIsLoading(false);
        }
    }, []);

    useEffect(() => {
        refreshAuth();
    }, [refreshAuth]);

    const login = useCallback(
        (token: string, data: { user: User; employee: Employee }) => {
            localStorage.setItem('api_token', token);
            if (data.user) setUser(data.user);
            refreshAuth();
        },
        [refreshAuth],
    );

    const logout = useCallback(async () => {
        try {
            await axiosInstance.post('/api/logout');
        } catch (e) {
            console.error(e);
        }
        localStorage.removeItem('api_token');
        setUser(null);
        globalThis.location.href = '/login';
    }, []);

    const authContextValue = useMemo(
        () => ({
            user,
            employee,
            menus,
            companyName,
            companyLogoUrl,
            translations,
            locale,
            regionalSettings,
            pendingApprovalsCount,
            isLoading,
            login,
            logout,
            refreshAuth,
        }),
        [
            user,
            employee,
            menus,
            companyName,
            companyLogoUrl,
            regionalSettings,
            translations,
            locale,
            pendingApprovalsCount,
            isLoading,
            login,
            logout,
            refreshAuth,
        ],
    );

    return (
        <AuthContext.Provider value={authContextValue}>
            {children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => useContext(AuthContext);
