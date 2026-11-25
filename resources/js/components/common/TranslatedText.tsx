'use client';

import { useTranslation } from '@/contexts/i18n-context';

export type TranslatableLabel = string | { key: string; fallback: string };

export function labelFallbackText(label: TranslatableLabel): string {
    return typeof label === 'string' ? label : label.fallback;
}

// `t()` mengembalikan kunci mentah bila kamus tidak punya kunci tsb; fallback dipakai agar kunci teknis tidak tampil.
export function TranslatedText({
    k,
    fallback,
}: Readonly<{ k: string; fallback: string }>) {
    const { t } = useTranslation();
    const translated = t(k);
    return <>{translated === k ? fallback : translated}</>;
}

export function useTranslatableText(label: TranslatableLabel): string {
    const { t } = useTranslation();
    if (typeof label === 'string') {
        return label;
    }
    const translated = t(label.key);
    return translated === label.key ? label.fallback : translated;
}

export function LabelText({ label }: Readonly<{ label: TranslatableLabel }>) {
    if (typeof label === 'string') {
        return <>{label}</>;
    }
    return <TranslatedText k={label.key} fallback={label.fallback} />;
}
