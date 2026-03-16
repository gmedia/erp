import { LucideIcon } from 'lucide-react';

interface IconProps {
    iconNode?: LucideIcon | null;
    className?: string;
}

export function Icon({ iconNode: IconComponent, className }: Readonly<IconProps>) {
    if (!IconComponent) {
        return null;
    }

    return <IconComponent className={className} />;
}
