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
    Book,
    FolderTree,
    BarChart,
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
    Book,
    FolderTree,
    BarChart,
};

export function getIcon(name: string | null): LucideIcon | undefined {
    if (!name) return undefined;
    return iconMap[name];
}
