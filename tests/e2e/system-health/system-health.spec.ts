import { expect, test, type Page, type Response } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { expectResponseOk, login } from '../helpers';

const HEALTH_VIEWER_EMAIL = 'system-health-viewer@example.com';
const NO_HEALTH_EMAIL = 'system-health-none@example.com';
const HEALTH_FIXTURE_PASSWORD = 'password';

function ensureSystemHealthFixtures(): void {
    const php = [
        `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${HEALTH_FIXTURE_PASSWORD}');`,
        `$perm = \\App\\Models\\Permission::updateOrCreate(['name' => 'system_health'], ['display_name' => 'System Health', 'parent_id' => null]);`,
        `$make = function (string $email, string $name, array $permIds) use ($hash) {`,
        `    $user = \\App\\Models\\User::updateOrCreate(['email' => $email], [`,
        `        'name' => $name, 'password' => $hash, 'email_verified_at' => now(),`,
        `    ]);`,
        `    $code = 'EMP-' . strtoupper(substr(md5($email), 0, 8));`,
        `    $emp = \\App\\Models\\Employee::updateOrCreate(['user_id' => $user->id], [`,
        `        'employee_id' => $code, 'name' => $name, 'email' => $email,`,
        `    ]);`,
        `    $emp->permissions()->sync($permIds);`,
        `};`,
        `$make('${HEALTH_VIEWER_EMAIL}', 'System Health Viewer', [$perm->id]);`,
        `$make('${NO_HEALTH_EMAIL}', 'No Health Access', []);`,
    ].join('\n');

    execFileSync('./vendor/bin/sail', ['artisan', 'tinker', `--execute=${php}`], {
        stdio: 'inherit',
    });
}

function waitForSystemHealthResponse(
    page: Page,
    method: 'GET' | 'POST',
): Promise<Response> {
    return page.waitForResponse(
        (response: Response) =>
            response.url().includes('/api/system-health') &&
            response.request().method() === method,
    );
}

test.describe('System Health', () => {
    test.beforeAll(() => {
        ensureSystemHealthFixtures();
    });

    test('halaman tampil dengan kartu status dan info versi', async ({ page }) => {
        await login(page);

        const responsePromise = waitForSystemHealthResponse(page, 'GET');
        await page.goto('/system-health');
        await expectResponseOk(await responsePromise);

        await expect(
            page.getByRole('heading', { name: 'System Health', exact: true }),
        ).toBeVisible();
        await expect(page.getByTestId('system-health-overall')).toBeVisible();
        await expect(page.getByTestId('system-health-card').first()).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Versi' })).toBeVisible();
        await expect(page.getByText('Laravel', { exact: true })).toBeVisible();
    });

    test('periksa sekarang memperbarui waktu pemeriksaan', async ({ page }) => {
        await login(page);

        const loadPromise = waitForSystemHealthResponse(page, 'GET');
        await page.goto('/system-health');
        await expectResponseOk(await loadPromise);

        const runPromise = waitForSystemHealthResponse(page, 'POST');
        await page.getByTestId('system-health-run-check').click();
        await expectResponseOk(await runPromise);

        await expect(page.getByTestId('system-health-overall')).toContainText(
            'Diperiksa pada',
        );
    });

    test('user tanpa permission system_health ditolak', async ({ page }) => {
        await login(page, NO_HEALTH_EMAIL, HEALTH_FIXTURE_PASSWORD, {
            requireDashboard: false,
        });

        const responsePromise = page.waitForResponse(
            (response: Response) =>
                response.url().includes('/api/system-health') &&
                response.request().method() === 'GET',
        );
        await page.goto('/system-health');
        const response = await responsePromise;

        expect(response.status()).toBe(403);
        await expect(page.getByTestId('system-health-card')).toHaveCount(0);
    });
});
