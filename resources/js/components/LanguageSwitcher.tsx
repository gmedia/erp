'use client';

import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLocale, useTranslation } from '@/contexts/i18n-context';
import { Globe } from 'lucide-react';

const localeLabels: Record<string, { label: string; flag: string }> = {
    en: { label: 'English', flag: '🇺🇸' },
    id: { label: 'Indonesia', flag: '🇮🇩' },
};

export function LanguageSwitcher() {
    const { locale, availableLocales, setLocale } = useLocale();
    const { t } = useTranslation();

    const currentLocale = localeLabels[locale] || localeLabels.en;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="w-full justify-start gap-2">
                    <Globe className="h-4 w-4" />
                    <span className="flex items-center gap-2">
                        <span>{currentLocale.flag}</span>
                        <span className="group-data-[collapsible=icon]:hidden">
                            {currentLocale.label}
                        </span>
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" className="w-40">
                {availableLocales.map((loc) => {
                    const localeInfo = localeLabels[loc] || { label: loc, flag: '🌐' };
                    return (
                        <DropdownMenuItem
                            key={loc}
                            onClick={() => setLocale(loc)}
                            className={locale === loc ? 'bg-accent' : ''}
                        >
                            <span className="mr-2">{localeInfo.flag}</span>
                            {localeInfo.label}
                        </DropdownMenuItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
