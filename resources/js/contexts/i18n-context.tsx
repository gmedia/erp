'use client';

import { useAuth } from '@/contexts/auth-context';
import { type Translations } from '@/types/i18n';
import {
    createContext,
    ReactNode,
    useCallback,
    useContext,
    useMemo,
} from 'react';

// Shared page props interface

// i18n context value interface
interface I18nContextValue {
    locale: string;
    availableLocales: string[];
    translations: Translations;
    setLocale: (locale: string) => void;
    t: (key: string, params?: Record<string, string>) => string;
}

// Create context with default values
const I18nContext = createContext<I18nContextValue | null>(null);

// Default fallback translations (English)
const defaultTranslations: Translations = {
    common: {
        save: 'Save',
        cancel: 'Cancel',
        delete: 'Delete',
        edit: 'Edit',
        add: 'Add',
        create: 'Create',
        update: 'Update',
        submit: 'Submit',
        view: 'View',
        close: 'Close',
        loading: 'Loading...',
        saving: 'Saving...',
        deleting: 'Deleting...',
        search: 'Search',
        actions: 'Actions',
        export: 'Export',
        reset_filters: 'Reset Filters',
        no_results: 'No results found',
        confirm: 'Confirm',
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        fill_details: 'Please fill in the details below.',
        view_details: 'Here are the details for this item.',
    },
    nav: {
        platform: 'Platform',
        dashboard: 'Dashboard',
        employees: 'Employees',
        positions: 'Positions',
        departments: 'Departments',
        repository: 'Repository',
        documentation: 'Documentation',
        settings: 'Settings',
        logout: 'Log Out',
        profile: 'Profile',
    },
    auth: {
        login: 'Log in',
        login_title: 'Log in to your account',
        login_description: 'Enter your email and password below to log in',
        email: 'Email address',
        password: 'Password',
        remember_me: 'Remember me',
        forgot_password: 'Forgot password?',
    },
    employees: {
        title: 'Employees',
        add: 'Add Employee',
        edit: 'Edit Employee',
        view: 'View Employee',
        search_placeholder: 'Search employees...',
        delete_confirm:
            "This action cannot be undone. This will permanently delete {name}'s employee record.",
        columns: {
            name: 'Name',
            email: 'Email',
            department: 'Department',
            position: 'Position',
            hire_date: 'Hire Date',
            created_at: 'Created At',
            updated_at: 'Updated At',
        },
    },
    departments: {
        title: 'Departments',
        add: 'Add Department',
        edit: 'Edit Department',
        view: 'View Department',
        search_placeholder: 'Search departments...',
        delete_confirm:
            "This action cannot be undone. This will permanently delete {name}'s department record.",
        columns: {
            name: 'Name',
            created_at: 'Created At',
            updated_at: 'Updated At',
        },
    },
    positions: {
        title: 'Positions',
        add: 'Add Position',
        edit: 'Edit Position',
        view: 'View Position',
        search_placeholder: 'Search positions...',
        delete_confirm:
            "This action cannot be undone. This will permanently delete {name}'s position record.",
        columns: {
            name: 'Name',
            created_at: 'Created At',
            updated_at: 'Updated At',
        },
    },
    form: {
        name: 'Name',
        name_placeholder: 'Enter name',
        email: 'Email',
        email_placeholder: 'Enter email',
        required: 'This field is required',
    },
    dialog: {
        are_you_sure: 'Are you sure?',
        delete_title: 'Delete Confirmation',
    },
    pagination: {
        previous: 'Previous',
        next: 'Next',
        page: 'Page',
        of: 'of',
        rows_per_page: 'Rows per page',
        showing: 'Showing',
        to: 'to',
        entries: 'entries',
    },
    table: {
        no_data: 'No data available',
        select_all: 'Select all',
        selected: 'selected',
    },
    language: {
        switch: 'Language',
        en: 'English',
        id: 'Indonesian',
    },
};

// Provider props - accepts translations as props for flexibility
interface I18nProviderProps {
    children: ReactNode;
    locale?: string;
    availableLocales?: string[];
    translations?: Translations;
}

/**
 * Base I18nProvider component that accepts translations as props.
 * Use AppI18nProvider for automatic app integration.
 */
export function I18nProvider({
    children,
    locale = 'en',
    availableLocales = ['en', 'id'],
    translations = defaultTranslations,
}: I18nProviderProps) {
    // Function to switch locale - triggers full page reload to ensure all translations are updated
    const setLocale = useCallback(async (newLocale: string) => {
        try {
            // Note: need to implement /locale/{locale} API later,
            // or we handle this differently if purely SPA.
            // For now, reload to fetch new translations from /api/v1/me
            await fetch(`/locale/${newLocale}`, { method: 'POST' });
            window.location.reload();
        } catch (e) {
            console.error(e);
        }
    }, []);

    /**
     * Translation function that supports nested keys (e.g., 'common.save')
     * and parameter interpolation (e.g., '{name}' -> 'John')
     */
    const t = useCallback(
        (key: string, params?: Record<string, string>): string => {
            const keys = key.split('.');
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            let value: any = translations;

            for (const k of keys) {
                if (value && typeof value === 'object' && k in value) {
                    value = value[k];
                } else {
                    // Key not found, return the key itself
                    return key;
                }
            }

            if (typeof value !== 'string') {
                return key;
            }

            // Interpolate parameters
            if (params) {
                return Object.entries(params).reduce(
                    (str, [paramKey, paramValue]) => {
                        return str.replace(
                            new RegExp(`\\{${paramKey}\\}`, 'g'),
                            paramValue,
                        );
                    },
                    value,
                );
            }

            return value;
        },
        [translations],
    );

    const contextValue = useMemo<I18nContextValue>(
        () => ({
            locale,
            availableLocales,
            translations,
            setLocale,
            t,
        }),
        [locale, availableLocales, translations, setLocale, t],
    );

    return (
        <I18nContext.Provider value={contextValue}>
            {children}
        </I18nContext.Provider>
    );
}

/**
 * App-aware I18nProvider that reads locale and translations from auth context.
 * Use this at the app level to wrap the entire application.
 */
export function AppI18nProvider({ children }: { children: ReactNode }) {
    const { locale, translations } = useAuth();

    // We can fetch availableLocales from an environment variable or globally define it
    const availableLocales = ['en', 'id'];

    return (
        <I18nProvider
            locale={locale || 'en'}
            availableLocales={availableLocales}
            translations={(translations as Translations) || defaultTranslations}
        >
            {children}
        </I18nProvider>
    );
}

/**
 * Hook to access the translation function.
 * Returns { t, locale, setLocale }
 */
export function useTranslation() {
    const context = useContext(I18nContext);
    if (!context) {
        throw new Error('useTranslation must be used within an I18nProvider');
    }
    return {
        t: context.t,
        locale: context.locale,
        setLocale: context.setLocale,
    };
}

/**
 * Hook to access locale information.
 * Returns { locale, availableLocales, setLocale }
 */
export function useLocale() {
    const context = useContext(I18nContext);
    if (!context) {
        throw new Error('useLocale must be used within an I18nProvider');
    }
    return {
        locale: context.locale,
        availableLocales: context.availableLocales,
        setLocale: context.setLocale,
    };
}

/**
 * Hook to get raw translations object.
 * Useful for accessing the entire translations structure.
 */
export function useTranslations(): Translations {
    const context = useContext(I18nContext);
    if (!context) {
        throw new Error('useTranslations must be used within an I18nProvider');
    }
    return context.translations;
}
