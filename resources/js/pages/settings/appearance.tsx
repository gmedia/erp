import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { useTranslation } from '@/contexts/i18n-context';
import { type BreadcrumbItem } from '@/types';
import { Helmet } from 'react-helmet-async';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

export default function Appearance() {
    const { t } = useTranslation();
    const title = t('settings.appearance.title');
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title,
            href: '/settings/appearance',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>
                    {title} - {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={title}
                        description={t('settings.appearance.description')}
                    />
                    <AppearanceTabs />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
