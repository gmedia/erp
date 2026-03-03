import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { ImgHTMLAttributes } from 'react';

const defaultLogoPath = '/asset-files/dokfin/logo_orange.svg';

export default function AppLogoIcon(
    props: ImgHTMLAttributes<HTMLImageElement>
) {
    const { companyLogoUrl } = usePage<SharedData>().props;

    return <img {...props} src={companyLogoUrl ?? defaultLogoPath} alt="App Logo" />;
}
