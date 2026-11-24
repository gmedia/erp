import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { Transition } from '@headlessui/react';
import { Loader2 } from 'lucide-react';
import type { ComponentType, ReactNode } from 'react';
import { Link } from 'react-router-dom';

/**
 * Pola halaman Settings (docs/ui-design-rules.md §0.4):
 * navigasi seksi vertikal di kiri; tiap baris = label tebal + deskripsi di kiri,
 * kontrol di kanan; Save (pill utama) di kanan atas seksi.
 */

export interface SettingsNavItem {
    title: string;
    href: string;
    icon?: ComponentType<{ className?: string }> | null;
    active: boolean;
}

export function SettingsShell({
    title,
    description,
    items,
    children,
}: Readonly<{
    title: string;
    description?: string;
    items: SettingsNavItem[];
    children: ReactNode;
}>) {
    return (
        <div className="mx-auto w-full max-w-6xl px-4 py-6">
            <div className="mb-8 space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight text-foreground">
                    {title}
                </h1>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>

            <div className="flex flex-col gap-6 lg:flex-row lg:gap-10">
                <aside className="w-full lg:w-52 lg:shrink-0">
                    <nav className="flex gap-1 overflow-x-auto lg:flex-col">
                        {items.map((item) => (
                            <Link
                                key={item.href}
                                to={item.href}
                                aria-current={item.active ? 'page' : undefined}
                                className={cn(
                                    'flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm transition-colors',
                                    item.active
                                        ? 'bg-card font-medium text-foreground'
                                        : 'text-muted-foreground hover:bg-card/60 hover:text-foreground',
                                )}
                            >
                                {item.icon && <item.icon className="size-4" />}
                                {item.title}
                            </Link>
                        ))}
                    </nav>
                </aside>

                <div className="min-w-0 flex-1 space-y-12">{children}</div>
            </div>
        </div>
    );
}

export function SettingsSection({
    title,
    description,
    actions,
    children,
}: Readonly<{
    title: string;
    description?: string;
    actions?: ReactNode;
    children: ReactNode;
}>) {
    return (
        <section className="space-y-2">
            <header className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-0.5">
                    <h2 className="text-base font-semibold">{title}</h2>
                    {description && (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    )}
                </div>
                {actions && (
                    <div className="flex items-center gap-3">{actions}</div>
                )}
            </header>
            {children}
        </section>
    );
}

export function SettingsRow({
    label,
    htmlFor,
    description,
    error,
    children,
}: Readonly<{
    label: ReactNode;
    htmlFor?: string;
    description?: ReactNode;
    error?: string;
    children: ReactNode;
}>) {
    return (
        <div className="grid gap-3 py-4 md:grid-cols-[minmax(0,1fr)_minmax(0,22rem)] md:gap-10">
            <div className="space-y-1">
                <Label htmlFor={htmlFor} className="font-medium">
                    {label}
                </Label>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            <div className="space-y-2">
                {children}
                {error && (
                    <p className="text-sm text-destructive-foreground">
                        {error}
                    </p>
                )}
            </div>
        </div>
    );
}

/** Tombol Save di header seksi; terhubung ke form lewat atribut `form`. */
export function SettingsSaveButton({
    formId,
    processing,
    recentlySuccessful,
    testId,
    dataTest,
    label = 'Save',
    hidden = false,
}: Readonly<{
    formId: string;
    processing: boolean;
    recentlySuccessful: boolean;
    testId?: string;
    /** Atribut `data-test` lama yang masih dipakai E2E. */
    dataTest?: string;
    label?: string;
    hidden?: boolean;
}>) {
    if (hidden) {
        return null;
    }

    return (
        <>
            <Transition
                show={recentlySuccessful}
                enter="transition ease-in-out duration-300"
                enterFrom="opacity-0"
                leave="transition ease-in-out duration-300"
                leaveTo="opacity-0"
            >
                <p className="text-sm text-success">Saved</p>
            </Transition>
            <Button
                type="submit"
                form={formId}
                disabled={processing}
                className="min-w-24"
                data-testid={testId}
                data-test={dataTest}
            >
                {processing ? (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                ) : null}
                {label}
            </Button>
        </>
    );
}
