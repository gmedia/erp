import { useAuth } from '@/contexts/auth-context';
import { ImgHTMLAttributes } from 'react';

const defaultLogoPath = '/asset-files/logo_orange.svg';

export default function AppLogoIcon(
    props: Readonly<ImgHTMLAttributes<HTMLImageElement>>,
) {
    const { companyLogoUrl } = useAuth();

    return (
        <img
            {...props}
            src={companyLogoUrl ?? defaultLogoPath}
            alt="App Logo"
        />
    );
}
