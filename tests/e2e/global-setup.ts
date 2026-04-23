import { execFileSync } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname } from 'node:path';

const AUTH_STATE_PATH = 'e2e/.auth/admin.json';
const DEFAULT_BASE_URL = 'http://localhost:80';
const VITE_HOT_FILE_PATH = 'public/hot';

type LoginResponse = {
    token?: string;
};

function resolvePhpBinary(): string {
    const candidates = [
        process.env.PLAYWRIGHT_PHP_BINARY,
        process.env.PHP_BINARY,
        'php',
        '/usr/bin/php',
        '/usr/local/bin/php',
    ].filter((candidate): candidate is string => Boolean(candidate && candidate.trim()));

    for (const candidate of candidates) {
        try {
            execFileSync(candidate, ['-v'], {
                stdio: 'ignore',
            });

            return candidate;
        } catch {
            // Try the next candidate.
        }
    }

    throw new Error('Unable to resolve PHP binary. Set PLAYWRIGHT_PHP_BINARY or PHP_BINARY.');
}

function disableViteHotFile(): void {
    // Force Laravel to use built assets during E2E runs.
    rmSync(VITE_HOT_FILE_PATH, { force: true });
}

async function createAdminAuthState(baseUrl: string, authStatePath: string): Promise<void> {
    const loginPayload = {
        email: 'admin@dokfin.id',
        password: 'password',
        device_name: 'playwright-global-setup',
    };

    let token = '';

    for (let attempt = 1; attempt <= 5; attempt++) {
        try {
            const response = await fetch(`${baseUrl}/api/login`, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(loginPayload),
            });

            if (! response.ok) {
                throw new Error(`Failed to login during global setup: ${response.status} ${response.statusText}`);
            }

            const payload = (await response.json()) as LoginResponse;
            if (! payload.token) {
                throw new Error('Login response does not contain token');
            }

            token = payload.token;
            break;
        } catch (error) {
            if (attempt === 5) {
                throw error;
            }

            await new Promise(resolve => setTimeout(resolve, attempt * 1000));
        }
    }

    const origin = new URL(baseUrl).origin;
    const storageState = {
        cookies: [],
        origins: [
            {
                origin,
                localStorage: [
                    {
                        name: 'api_token',
                        value: token,
                    },
                ],
            },
        ],
    };

    mkdirSync(dirname(authStatePath), { recursive: true });
    writeFileSync(authStatePath, JSON.stringify(storageState, null, 2), 'utf8');
}

export default async function globalSetup() {
    const phpBinary = resolvePhpBinary();
    const baseUrl = process.env.PLAYWRIGHT_BASE_URL || DEFAULT_BASE_URL;
    const shouldPreloadAuthState = process.env.PLAYWRIGHT_PRELOAD_AUTH !== '0';
    const authStatePath = process.env.PLAYWRIGHT_STORAGE_STATE || AUTH_STATE_PATH;

    disableViteHotFile();

    execFileSync(phpBinary, ['artisan', 'migrate:fresh', '--force'], {
        stdio: 'inherit',
    });
    execFileSync(phpBinary, ['artisan', 'db:seed', '--force'], {
        stdio: 'inherit',
    });

    if (shouldPreloadAuthState) {
        await createAdminAuthState(baseUrl, authStatePath);
    }
}
