import { SecureStorage } from '@aparajita/capacitor-secure-storage';
import { Capacitor } from '@capacitor/core';

const TOKEN_KEY = 'api_token';
const STORAGE_PREFIX = 'erp';

let cachedToken: string | null = null;
let initialized = false;

function isNative(): boolean {
    return Capacitor.isNativePlatform();
}

export async function initTokenStorage(): Promise<void> {
    if (initialized) {
        return;
    }

    if (!isNative()) {
        cachedToken = localStorage.getItem(TOKEN_KEY);
        initialized = true;
        return;
    }

    try {
        await SecureStorage.setKeyPrefix(STORAGE_PREFIX);
        const stored = await SecureStorage.get(TOKEN_KEY);
        cachedToken = typeof stored === 'string' ? stored : null;
    } catch (error) {
        console.error('Failed to read secure token', error);
        cachedToken = null;
    }

    initialized = true;
}

export function getToken(): string | null {
    if (!initialized && !isNative()) {
        return localStorage.getItem(TOKEN_KEY);
    }

    return cachedToken;
}

export function setToken(token: string): void {
    cachedToken = token;
    initialized = true;

    if (isNative()) {
        void SecureStorage.set(TOKEN_KEY, token).catch((error) => {
            console.error('Failed to persist secure token', error);
        });

        return;
    }

    localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken(): void {
    cachedToken = null;
    initialized = true;

    if (isNative()) {
        void SecureStorage.remove(TOKEN_KEY).catch((error) => {
            console.error('Failed to remove secure token', error);
        });

        return;
    }

    localStorage.removeItem(TOKEN_KEY);
}
