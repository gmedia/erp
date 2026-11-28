import { expect, test, type Page, type Response } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { expectResponseOk, login } from '../helpers';

const NO_ACCESS_EMAIL = 'mobile-app-none@example.com';
const FIXTURE_PASSWORD = 'password';

function ensureNoAccessFixture(): void {
    const php = [
        `$hash = \\Illuminate\\Support\\Facades\\Hash::make('${FIXTURE_PASSWORD}');`,
        `$user = \\App\\Models\\User::updateOrCreate(['email' => '${NO_ACCESS_EMAIL}'], [`,
        `    'name' => 'Mobile App No Access',`,
        `    'password' => $hash,`,
        `    'email_verified_at' => now(),`,
        `]);`,
        `$emp = \\App\\Models\\Employee::updateOrCreate(['user_id' => $user->id], [`,
        `    'employee_id' => 'EMP-MOBILE-NOACCESS',`,
        `    'name' => 'Mobile App No Access',`,
        `    'email' => '${NO_ACCESS_EMAIL}',`,
        `]);`,
        `$emp->permissions()->sync([]);`,
    ].join('\n');

    execFileSync(
        './vendor/bin/sail',
        ['artisan', 'tinker', `--execute=${php}`],
        { stdio: 'inherit' },
    );
}

function waitForApi(
    page: Page,
    matcher: string,
    method: string,
): Promise<Response> {
    return page.waitForResponse(
        (response) =>
            response.url().includes(matcher) &&
            response.request().method() === method,
    );
}

test.describe('Mobile App', () => {
    test('admin uploads an APK and the download page shows version and QR', async ({
        page,
    }) => {
        const versionName = `9.9.${Date.now().toString().slice(-6)}`;
        const versionCode = Math.floor(Date.now() / 1000);
        const apk = Buffer.from([
            0x50, 0x4b, 0x05, 0x06, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
            0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
        ]);

        await login(page);

        const listResponse = waitForApi(page, '/api/mobile-app-releases', 'GET');
        await page.goto('/mobile-app-releases');
        await expectResponseOk(await listResponse);
        await expect(
            page.getByRole('heading', { name: 'Mobile App Releases' }),
        ).toBeVisible();

        await page.getByTestId('mobile-app-upload-open').click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await page.locator('#mobile_app_apk').setInputFiles({
            name: 'mobile-app-test.apk',
            mimeType: 'application/vnd.android.package-archive',
            buffer: apk,
        });
        await dialog.locator('input[name="version_name"]').fill(versionName);
        await dialog
            .locator('input[name="version_code"]')
            .fill(String(versionCode));
        await dialog
            .locator('input[name="min_supported_version_code"]')
            .fill('1');
        await dialog
            .locator('textarea[name="notes"]')
            .fill('Catatan rilis pengujian otomatis.');

        const createResponse = waitForApi(page, '/api/mobile-app-releases', 'POST');
        await page.getByTestId('mobile-app-upload-submit').click();
        await expectResponseOk(await createResponse);
        await expect(dialog).not.toBeVisible({ timeout: 15000 });

        const row = page
            .getByTestId('mobile-app-release-row')
            .filter({ hasText: versionName });
        await expect(row).toBeVisible();

        const activateResponse = waitForApi(
            page,
            '/api/mobile-app-releases/',
            'PATCH',
        );
        await row.getByTestId('mobile-app-release-toggle').click();
        await expectResponseOk(await activateResponse);
        await expect(row.getByText('Active', { exact: true })).toBeVisible();

        const activeResponse = waitForApi(page, '/api/mobile-app', 'GET');
        await page.goto('/mobile-app');
        await expectResponseOk(await activeResponse);

        await expect(
            page.getByRole('heading', { name: 'Download App' }),
        ).toBeVisible();
        await expect(page.getByText(versionName, { exact: true })).toBeVisible();
        await expect(page.getByTestId('mobile-app-qr')).toBeVisible();
        await expect(
            page.getByTestId('mobile-app-qr').locator('svg'),
        ).toBeVisible();
        await expect(
            page.getByTestId('mobile-app-download-button'),
        ).toBeVisible();
    });

    test('user without mobile_app_release permission cannot open the admin page', async ({
        page,
    }) => {
        ensureNoAccessFixture();

        await login(page, NO_ACCESS_EMAIL, FIXTURE_PASSWORD, {
            requireDashboard: false,
        });

        await page.goto('/mobile-app-releases');
        await expect(page).toHaveURL(/\/dashboard/);
        await expect(
            page.getByRole('heading', { name: 'Mobile App Releases' }),
        ).toHaveCount(0);
    });
});
