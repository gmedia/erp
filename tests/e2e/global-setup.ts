import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readdirSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';

const AUTH_STATE_PATH = 'e2e/.auth/admin.json';
const DEFAULT_BASE_URL = 'http://localhost:80';
const VITE_HOT_FILE_PATH = 'public/hot';
const SAIL_BINARY_PATH = 'vendor/bin/sail';
const BUILD_MANIFEST_PATH = 'public/build/manifest.json';
const BUILD_SOURCE_DIRS = ['resources'];
const BUILD_SOURCE_FILES = ['package.json', 'package-lock.json', 'vite.config.ts', 'tsconfig.json'];
const BUILD_IGNORE_DIRS = new Set(['node_modules', '.git', 'vendor', 'storage', 'public']);

type LoginResponse = {
    token?: string;
};

type CommandRunner = {
    command: string;
    baseArgs: string[];
    label: string;
};

function tryLocalPhpBinary(): string | null {
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

    return null;
}

function tryLocalNpmBinary(): string | null {
    const candidates = [
        process.env.PLAYWRIGHT_NPM_BINARY,
        'npm',
        '/usr/bin/npm',
        '/usr/local/bin/npm',
    ].filter((candidate): candidate is string => Boolean(candidate && candidate.trim()));

    for (const candidate of candidates) {
        try {
            execFileSync(candidate, ['--version'], {
                stdio: 'ignore',
            });

            return candidate;
        } catch {
            // Try the next candidate.
        }
    }

    return null;
}

function resolveNpmRunner(): CommandRunner | null {
    const forceSail = process.env.PLAYWRIGHT_USE_SAIL === '1';

    if (! forceSail) {
        const npmBinary = tryLocalNpmBinary();
        if (npmBinary) {
            return {
                command: npmBinary,
                baseArgs: [],
                label: `local npm (${npmBinary})`,
            };
        }
    }

    if (existsSync(SAIL_BINARY_PATH)) {
        return {
            command: SAIL_BINARY_PATH.startsWith('/') ? SAIL_BINARY_PATH : `./${SAIL_BINARY_PATH}`,
            baseArgs: ['npm'],
            label: 'Sail (./vendor/bin/sail npm)',
        };
    }

    return null;
}

function findNewestMtime(path: string): number {
    let stat;
    try {
        stat = statSync(path);
    } catch {
        return 0;
    }

    if (stat.isFile()) {
        return stat.mtimeMs;
    }

    if (! stat.isDirectory()) {
        return 0;
    }

    let newest = stat.mtimeMs;
    let entries;
    try {
        entries = readdirSync(path, { withFileTypes: true });
    } catch {
        return newest;
    }

    for (const entry of entries) {
        if (entry.isDirectory() && BUILD_IGNORE_DIRS.has(entry.name)) {
            continue;
        }

        const childMtime = findNewestMtime(join(path, entry.name));
        if (childMtime > newest) {
            newest = childMtime;
        }
    }

    return newest;
}

function isBuildStale(): boolean {
    if (! existsSync(BUILD_MANIFEST_PATH)) {
        return true;
    }

    const manifestMtime = statSync(BUILD_MANIFEST_PATH).mtimeMs;
    let newestSourceMtime = 0;

    for (const dir of BUILD_SOURCE_DIRS) {
        const mtime = findNewestMtime(dir);
        if (mtime > newestSourceMtime) {
            newestSourceMtime = mtime;
        }
    }

    for (const file of BUILD_SOURCE_FILES) {
        const mtime = findNewestMtime(file);
        if (mtime > newestSourceMtime) {
            newestSourceMtime = mtime;
        }
    }

    return newestSourceMtime > manifestMtime;
}

function ensureFreshBuild(): void {
    if (process.env.PLAYWRIGHT_SKIP_BUILD === '1') {
        console.log('[e2e:global-setup] PLAYWRIGHT_SKIP_BUILD=1 set, skipping build hygiene check');
        return;
    }

    const force = process.env.PLAYWRIGHT_FORCE_BUILD === '1';
    if (! force && ! isBuildStale()) {
        return;
    }

    const npm = resolveNpmRunner();
    if (! npm) {
        console.warn(
            '[e2e:global-setup] Build appears stale but no npm runner is available. '
                + 'Run `sail npm run build` manually or set PLAYWRIGHT_SKIP_BUILD=1 to silence.',
        );
        return;
    }

    const reason = force ? 'PLAYWRIGHT_FORCE_BUILD=1' : 'source files newer than build manifest';
    console.log(`[e2e:global-setup] Rebuilding Vite assets (${reason}) via ${npm.label}`);

    execFileSync(npm.command, [...npm.baseArgs, 'run', 'build'], {
        stdio: 'inherit',
    });
}

function resolveArtisanRunner(): CommandRunner {
    const forceSail = process.env.PLAYWRIGHT_USE_SAIL === '1';

    if (! forceSail) {
        const phpBinary = tryLocalPhpBinary();
        if (phpBinary) {
            return {
                command: phpBinary,
                baseArgs: ['artisan'],
                label: `local PHP (${phpBinary})`,
            };
        }
    }

    if (existsSync(SAIL_BINARY_PATH)) {
        return {
            command: SAIL_BINARY_PATH.startsWith('/') ? SAIL_BINARY_PATH : `./${SAIL_BINARY_PATH}`,
            baseArgs: ['artisan'],
            label: 'Sail (./vendor/bin/sail)',
        };
    }

    throw new Error(
        'Unable to resolve artisan runner. Install PHP locally, set PLAYWRIGHT_PHP_BINARY/PHP_BINARY, '
            + 'or ensure vendor/bin/sail exists (run `composer install`).',
    );
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
                let body = '';
                try {
                    body = await response.text();
                } catch (_) {
                    body = '[could not read body]';
                }
                throw new Error(`Failed to login during global setup: ${response.status} ${response.statusText}. Body: ${body}`);
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
    const runner = resolveArtisanRunner();
    const baseUrl = process.env.PLAYWRIGHT_BASE_URL || DEFAULT_BASE_URL;
    const shouldPreloadAuthState = process.env.PLAYWRIGHT_PRELOAD_AUTH !== '0';
    const authStatePath = process.env.PLAYWRIGHT_STORAGE_STATE || AUTH_STATE_PATH;

    console.log(`[e2e:global-setup] Using artisan runner: ${runner.label}`);

    disableViteHotFile();
    ensureFreshBuild();

    execFileSync(runner.command, [...runner.baseArgs, 'migrate:fresh', '--force'], {
        stdio: 'inherit',
    });
    execFileSync(runner.command, [...runner.baseArgs, 'db:seed', '--force'], {
        stdio: 'inherit',
    });

    // Ensure the public storage symlink exists so /storage/exports/*.xlsx
    // download URLs returned by Storage::disk('public')->url(...) resolve to
    // real files instead of HTML 404 pages. Without this, ExcelJS fails with
    // "Can't find end of central directory : is this a zip file?".
    try {
        execFileSync(runner.command, [...runner.baseArgs, 'storage:link', '--force'], {
            stdio: 'inherit',
        });
    } catch {
        // storage:link is idempotent; ignore "already exists" failures from
        // older Laravel versions that don't accept --force.
    }

    if (shouldPreloadAuthState) {
        await createAdminAuthState(baseUrl, authStatePath);
    }
}
