import { ImgHTMLAttributes } from 'react';

export default function AppLogoIcon(
    props: ImgHTMLAttributes<HTMLImageElement>
) {
    return (
        <img
            src="/asset-files/dokfin/logo_orange.svg"
            alt="App Logo"
            {...props}
        />
    );
}
