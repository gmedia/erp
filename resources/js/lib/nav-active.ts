export function normalizeNavPath(path: string): string {
    if (!path || path === '#') {
        return '';
    }

    const trimmed = path.replace(/\/+$/, '');
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

export function isNavHrefActive(
    pathname: string,
    href: string,
    allHrefs: readonly string[],
): boolean {
    const active = resolveActiveNavHref(pathname, allHrefs);
    if (!active) {
        return false;
    }

    return normalizeNavPath(href) === active;
}
