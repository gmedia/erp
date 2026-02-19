'use client';

import { router, usePage } from '@inertiajs/react';
import {
    createContext,
    ReactNode,
    useCallback,
    useContext,
    useMemo,
} from 'react';

// Types for translation data
export interface Translations {
    common: {
        save: string;
        cancel: string;
        delete: string;
        edit: string;
        add: string;
        create: string;
        update: string;
        submit: string;
        view: string;
        close: string;
        loading: string;
        saving: string;
        deleting: string;
        search: string;
        actions: string;
        export: string;
        reset_filters: string;
        no_results: string;
        confirm: string;
        success: string;
        error: string;
        warning: string;
        fill_details: string;
        view_details: string;
    };
    nav: {
        platform: string;
        dashboard: string;
        employees: string;
        positions: string;
        departments: string;
        repository: string;
        documentation: string;
        settings: string;
        logout: string;
        profile: string;
    };
    auth: {
        login: string;
        login_title: string;
        login_description: string;
        email: string;
        password: string;
        remember_me: string;
        forgot_password: string;
    };
    employees: {
        title: string;
        add: string;
        edit: string;
        view: string;
        search_placeholder: string;
        delete_confirm: string;
        columns: {
            name: string;
            email: string;
            department: string;
            position: string;
            hire_date: string;
            created_at: string;
            updated_at: string;
        };
    };
    departments: {
        title: string;
        add: string;
        edit: string;
        view: string;
        search_placeholder: string;
        delete_confirm: string;
        columns: {
            name: string;
            created_at: string;
            updated_at: string;
        };
    };
    positions: {
        title: string;
        add: string;
        edit: string;
        view: string;
        search_placeholder: string;
        delete_confirm: string;
        columns: {
            name: string;
            created_at: string;
            updated_at: string;
        };
    };
    form: {
        name: string;
        name_placeholder: string;
        email: string;
        email_placeholder: string;
        required: string;
    };
    dialog: {
        are_you_sure: string;
        delete_title: string;
    };
    pagination: {
        previous: string;
        next: string;
        page: string;
        of: string;
        rows_per_page: string;
        showing: string;
        to: string;
        entries: string;
    };
    table: {
        no_data: string;
        select_all: string;
        selected: string;
    };
    language: {
        switch: string;
        en: string;
        id: string;
    };
}

// Shared page props interface
interface SharedPageProps {
    locale: string;
    availableLocales: string[];
    translations: Translations;
    [key: string]: unknown;
}

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
 * Use InertiaI18nProvider for automatic Inertia integration.
 */
export function I18nProvider({
    children,
    locale = 'en',
    availableLocales = ['en', 'id'],
    translations = defaultTranslations,
}: I18nProviderProps) {
    // Function to switch locale - triggers full page reload to ensure all translations are updated
    const setLocale = useCallback((newLocale: string) => {
        router.post(
            `/locale/${newLocale}`,
            {},
            {
                preserveState: false,
                preserveScroll: false,
                onSuccess: () => {
                    window.location.reload();
                },
            },
        );
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
 * Inertia-aware I18nProvider that reads locale and translations from Inertia shared data.
 * Use this at the app level to wrap the entire application.
 */
export function InertiaI18nProvider({ children }: { children: ReactNode }) {
    const { props } = usePage<SharedPageProps>();

    return (
        <I18nProvider
            locale={props.locale || 'en'}
            availableLocales={props.availableLocales || ['en', 'id']}
            translations={props.translations || defaultTranslations}
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
