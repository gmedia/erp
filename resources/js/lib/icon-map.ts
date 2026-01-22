import {
    BookOpen,
    Folder,
    IdCard,
    LayoutGrid,
    LucideIcon,
    Settings2,
    LayoutList,
    Users,
} from 'lucide-react';

const iconMap: Record<string, LucideIcon> = {
    LayoutGrid,
    Users,
    IdCard,
    Settings2,
    LayoutList,
    BookOpen,
    Folder,
};

export function getIcon(name: string | null): LucideIcon | undefined {
    if (!name) return undefined;
    return iconMap[name];
}
