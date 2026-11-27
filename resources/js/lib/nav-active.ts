export function normalizeNavPath(path: string): string {
    if (!path || path === '#') {
        return '';
    }

    let end = path.length;
    while (end > 0 && path[end - 1] === '/') {
        end--;
    }
    const trimmed = path.slice(0, end);
    if (trimmed === '') {
        return '/';
    }

    return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
}

export function pathMatchesNavHref(pathname: string, href: string): boolean {
    const path = normalizeNavPath(pathname);
    const base = normalizeNavPath(href);

    if (!path || !base) {
        return false;
    }

    return path === base || path.startsWith(`${base}/`);
}

export function resolveActiveNavHref(
    pathname: string,
    hrefs: readonly string[],
): string {
    let best = '';
    let bestLen = -1;

    for (const href of hrefs) {
        if (!pathMatchesNavHref(pathname, href)) {
            continue;
        }
        const normalized = normalizeNavPath(href);
        if (normalized.length > bestLen) {
            best = normalized;
            bestLen = normalized.length;
        }
    }

    return best;
}

const VEHICLE_LOAN_PREFIX = '/vehicle-loans';
const VEHICLE_LOAN_PARENT_HREF = '/vehicle-loans/request';

export function resolveNavPathname(pathname: string): string {
    const path = normalizeNavPath(pathname);
    const rest = path.startsWith(`${VEHICLE_LOAN_PREFIX}/`)
        ? path.slice(VEHICLE_LOAN_PREFIX.length + 1)
        : '';
    const isFlowPage =
        rest === 'pickup' || rest === 'return' || /^\d+$/.test(rest);

    return isFlowPage ? VEHICLE_LOAN_PARENT_HREF : path;
}

export function isNavHrefActive(
    pathname: string,
    href: string,
    allHrefs: readonly string[],
): boolean {
    const active = resolveActiveNavHref(resolveNavPathname(pathname), allHrefs);
    if (!active) {
        return false;
    }

    return normalizeNavPath(href) === active;
}
