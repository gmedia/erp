import {
    BookOpen,
    Folder,
    IdCard,
    LayoutGrid,
    LucideIcon,
    Settings2,
    LayoutList,
    Users,
    Truck,
} from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
    LayoutGrid,
    Users,
    IdCard,
    Settings2,
    LayoutList,
    BookOpen,
    Folder,
    Truck,
};

export function getIcon(name: string | null): LucideIcon | undefined {
    if (!name) return undefined;
    return iconMap[name];
}
