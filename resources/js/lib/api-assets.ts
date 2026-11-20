const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '';

export function resolveApiAssetUrl(
    path: string | null | undefined,
): string | undefined {
    if (!path) {
        return undefined;
    }

    if (/^(?:https?:|data:|blob:)/i.test(path)) {
        return path;
    }

    const normalizedPath = path.startsWith('/') ? path : '/' + path;

    return `${API_BASE_URL}${normalizedPath}`;
}
