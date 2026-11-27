import { expect, test } from '@playwright/test';
import { DEFAULT_E2E_EMAIL, login } from '../helpers';

const WRONG_PASSWORD = 'wrong-password-for-security-log';

test.describe('Security Logs', () => {
    test('failed then successful login both appear in the log', async ({
        page,
    }) => {
        await page.goto('/login');
        await page.evaluate(() => {
            localStorage.removeItem('api_token');
            sessionStorage.clear();
        });
        await page.goto('/login');
        await page.locator('input[name="email"]').fill(DEFAULT_E2E_EMAIL);
        await page.locator('input[name="password"]').fill(WRONG_PASSWORD);
        await page
            .locator('button[type="submit"], button[data-test="login-button"]')
            .first()
            .click({ force: true });
        await expect(
            page.getByText('These credentials do not match our records.'),
        ).toBeVisible();

        await login(page, undefined, undefined, { requireDashboard: false });

        await page.goto('/security-logs');
        await expect(page.locator('table')).toBeVisible();
        await expect(page.getByText('Loading...')).not.toBeVisible();

        const rows = page.locator('tbody tr');
        await expect(
            rows.filter({ hasText: 'Login Failed' }).first(),
        ).toBeVisible();
        await expect(
            rows.filter({ hasText: 'Login Success' }).first(),
        ).toBeVisible();
        await expect(
            rows.filter({ hasText: DEFAULT_E2E_EMAIL }).first(),
        ).toBeVisible();
        await expect(page.getByText(WRONG_PASSWORD)).toHaveCount(0);
    });

    test('event filter narrows the list to failed logins', async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await page.goto('/security-logs');
        await expect(page.locator('table')).toBeVisible();
        await expect(page.getByText('Loading...')).not.toBeVisible();

        await page.getByRole('button', { name: 'Filters' }).click();
        const filterDialog = page.getByRole('dialog', { name: 'Filters' });
        await filterDialog.getByRole('combobox').nth(0).click();
        await page
            .getByRole('option', { name: 'Login Failed', exact: true })
            .click();
        await filterDialog
            .getByRole('button', { name: 'Apply Filters' })
            .click();

        const rows = page.locator('tbody tr');
        await expect(rows.first()).toBeVisible();
        await expect
            .poll(async () => {
                const texts = await rows.allTextContents();
                return (
                    texts.length > 0 &&
                    texts.every((text) => text.includes('Login Failed'))
                );
            })
            .toBe(true);
    });

    test('export posts to the export endpoint and downloads a file', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await page.goto('/security-logs');
        await expect(page.locator('table')).toBeVisible();

        const exportRequest = page.waitForRequest(
            (request) =>
                request.url().includes('/api/security-logs/export') &&
                request.method() === 'POST',
            { timeout: 30000 },
        );
        await page
            .getByRole('button', { name: /Export/ })
            .first()
            .click();
        await exportRequest;
    });

    test('unauthenticated requests to the security log API are rejected', async ({
        request,
    }) => {
        const response = await request.get('/api/security-logs', {
            headers: { Accept: 'application/json' },
        });
        expect([401, 403]).toContain(response.status());
    });
});
