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
): TrailNode[] | null {
    for (const item of items) {
        const href = normalizePath(item.url);
        const title = item.display_name || item.name;
        const here = [...ancestors, { title, href }];

        if (href && pathMatches(pathname, href)) {
            const deeper = item.children
                ? findMenuTrail(item.children, pathname, here)
                : null;

            return deeper ?? here;
        }

        if (item.children?.length) {
            const nested = findMenuTrail(item.children, pathname, here);
            if (nested) {
                return nested;
            }
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
): BreadcrumbItem[] | null {
    const path = normalizePath(pathname);
    if (!path) {
        return null;
    }

    const trail = findMenuTrail(menus, path, []);
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
): BreadcrumbItem[] {
    const menuTrail = resolveMenuBreadcrumbTrail(menus, pathname);

    if (explicit && explicit.length >= 2) {
        return [...explicit];
    }

    if (menuTrail && menuTrail.length > 0) {
        return menuTrail;
    }

    return explicit ? [...explicit] : [];
}
