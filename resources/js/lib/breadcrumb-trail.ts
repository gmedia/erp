import type { BreadcrumbItem } from '@/types';

type TrailMenuNode = {
    name: string;
    display_name: string;
    url: string | null;
    children?: readonly TrailMenuNode[];
};

type TrailNode = {
    title: string;
    href: string;
};

export type MenuTitleResolver = (name: string, displayName: string) => string;

const fallbackTitle: MenuTitleResolver = (_name, displayName) => displayName;

function normalizePath(path: string | null | undefined): string {
    if (!path) {
        return '';
    }

    const trimmed = path.trim();
    if (!trimmed || trimmed === '#') {
        return '';
    }

    const withSlash = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;

    return withSlash.length > 1 ? withSlash.replace(/\/+$/, '') : withSlash;
}

function pathMatches(pathname: string, href: string): boolean {
    if (!href) {
        return false;
    }

    return pathname === href || pathname.startsWith(`${href}/`);
}

function findMenuTrail(
    items: readonly TrailMenuNode[],
    pathname: string,
    ancestors: TrailNode[],
    resolveTitle: MenuTitleResolver,
): TrailNode[] | null {
    for (const item of items) {
        const href = normalizePath(item.url);
        const title = resolveTitle(item.name, item.display_name || item.name);
        const here = [...ancestors, { title, href }];

        if (href && pathMatches(pathname, href)) {
            const deeper = item.children
                ? findMenuTrail(item.children, pathname, here, resolveTitle)
                : null;

            // Halaman detail (mis. /asset-stocktakes/:id/perform) bukan lokasi
            // menu; kembalikan null agar breadcrumb eksplisit halaman dipakai.
            return deeper ?? (href === pathname ? here : null);
        }

        if (item.children?.length) {
            const nested = findMenuTrail(
                item.children,
                pathname,
                here,
                resolveTitle,
            );
            if (nested) {
                return nested;
            }
        }
    }

    return null;
}

function findMenuTitleByDisplayName(
    items: readonly TrailMenuNode[],
    displayName: string,
): TrailMenuNode | null {
    for (const item of items) {
        if (item.display_name === displayName) {
            return item;
        }

        const nested = item.children
            ? findMenuTitleByDisplayName(item.children, displayName)
            : null;
        if (nested) {
            return nested;
        }
    }

    return null;
}

/**
 * Satu aturan breadcrumb dari menu tree (W4-06): grup menu → halaman.
 * Mengembalikan null bila path tidak ada di menu (mis. halaman detail).
 */
export function resolveMenuBreadcrumbTrail(
    menus: readonly TrailMenuNode[],
    pathname: string,
    resolveTitle: MenuTitleResolver = fallbackTitle,
): BreadcrumbItem[] | null {
    const path = normalizePath(pathname);
    if (!path) {
        return null;
    }

    const trail = findMenuTrail(menus, path, [], resolveTitle);
    if (!trail || trail.length === 0) {
        return null;
    }

    return trail
        .filter((node) => node.title)
        .map((node, index, all) => ({
            title: node.title,
            href: node.href || (index === all.length - 1 ? path : '#'),
        }));
}

/**
 * Breadcrumb final (satu aturan, W4-06):
 * - Halaman detail yang mendefinisikan breadcrumb berisi grup (≥2 item) tetap
 *   memakai definisinya.
 * - Selain itu turunkan trail grup → halaman dari menu tree, sehingga CRUD
 *   yang hanya punya satu item (mis. "Departments") tetap menampilkan grupnya.
 */
export function resolveBreadcrumbs(
    menus: readonly TrailMenuNode[],
    pathname: string,
    explicit: readonly BreadcrumbItem[] | undefined,
    resolveTitle: MenuTitleResolver = fallbackTitle,
): BreadcrumbItem[] {
    const menuTrail = resolveMenuBreadcrumbTrail(menus, pathname, resolveTitle);

    if (!menuTrail || menuTrail.length === 0) {
        return explicit ? [...explicit] : [];
    }

    // Rangka (grup) dari menu tree; label daun = label halaman (H1) yang
    // diberikan halaman lewat breadcrumb eksplisitnya.
    const leaf =
        explicit && explicit.length > 0
            ? explicit[explicit.length - 1]
            : undefined;

    if (!leaf) {
        return menuTrail;
    }

    const merged = [...menuTrail];
    const leafMenu = findMenuTitleByDisplayName(menus, leaf.title);
    merged[merged.length - 1] = {
        title: leafMenu
            ? resolveTitle(leafMenu.name, leafMenu.display_name)
            : leaf.title,
        href:
            leaf.href && leaf.href !== '#' ? leaf.href : menuTrail.at(-1)!.href,
    };

    return merged;
}
