import { expect, test, type Page } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { login } from '../helpers';

const VIEWER_EMAIL = 'special-action-viewer@example.com';
const FIXTURE_PASSWORD = 'password';

const FIXTURE_PHP = [
    `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${FIXTURE_PASSWORD}');`,
    `$names = ['asset_depreciation_run', 'posting_journal'];`,
    `$perms = \\App\\Models\\Permission::whereIn('name', $names)->get()->keyBy('name');`,
    `$user = \\App\\Models\\User::updateOrCreate(['email' => '${VIEWER_EMAIL}'], [`,
    `    'name' => 'Special Viewer', 'password' => $hash, 'email_verified_at' => now(),`,
    `]);`,
    `$code = 'EMP-' . strtoupper(substr(md5('${VIEWER_EMAIL}'), 0, 8));`,
    `$emp = \\App\\Models\\Employee::updateOrCreate(['user_id' => $user->id], [`,
    `    'employee_id' => $code, 'name' => 'Special Viewer', 'email' => '${VIEWER_EMAIL}',`,
    `]);`,
    `$emp->permissions()->sync([$perms['asset_depreciation_run']->id, $perms['posting_journal']->id]);`,
].join('\n');

function ensureViewerFixture(): void {
    execFileSync(
        './vendor/bin/sail',
        ['artisan', 'tinker', `--execute=${FIXTURE_PHP}`],
        { stdio: 'inherit' },
    );
}

async function openPage(page: Page, path: string, apiPath: string) {
    const listResponse = page.waitForResponse(
        (r) =>
            r.url().includes(apiPath) &&
            r.request().method() === 'GET' &&
            r.status() < 400,
    );
    await page.goto(path);
    await listResponse;
}

test.describe('Special page action permissions (lihat-saja)', () => {
    test.beforeAll(() => {
        ensureViewerFixture();
    });

    test.beforeEach(async ({ page }) => {
        await login(page, VIEWER_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });
    });

    test('asset-depreciation-runs: tombol Run Calculation tidak tampil', async ({
        page,
    }) => {
        await openPage(
            page,
            '/asset-depreciation-runs',
            '/api/asset-depreciation-runs',
        );

        await expect(
            page.getByRole('button', { name: 'Run Calculation' }),
        ).toHaveCount(0);
    });

    test('posting-journals: checkbox dan Post Selected tidak tampil', async ({
        page,
    }) => {
        await openPage(page, '/posting-journals', '/api/posting-journals');

        await expect(
            page.getByRole('button', { name: /Post Selected/ }),
        ).toHaveCount(0);
        await expect(page.getByRole('checkbox')).toHaveCount(0);
    });
});
