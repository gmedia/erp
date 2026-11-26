import { expect, test } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';

const LOCALE_KEY = 'app_locale';

test.describe('i18n pipeline dashboard', () => {
    test('pipeline dashboard labels are Indonesian (locale id)', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page);

        const dataPromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/pipeline-dashboard/data') &&
                response.request().method() === 'GET',
            { timeout: 15000 },
        );
        await page.goto('/pipeline-dashboard', {
            waitUntil: 'domcontentloaded',
        });
        await expectResponseOk(await dataPromise);

        await expect(
            page.getByText('Pilih Pipeline', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Ambang Batas Macet', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Distribusi Status', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Entitas Macet', { exact: true }),
        ).toBeVisible();

        await expect(
            page.getByText('Select Pipeline', { exact: true }),
        ).toHaveCount(0);
        await expect(
            page.getByText('Stale Threshold', { exact: true }),
        ).toHaveCount(0);
        await expect(
            page.getByText('Stale Entities', { exact: true }),
        ).toHaveCount(0);
    });

    test('stock adjustment report filter labels are Indonesian (locale id)', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'id');
        }, LOCALE_KEY);

        await login(page, undefined, undefined, { requireDashboard: false });

        const reportPromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/reports/stock-adjustment') &&
                response.request().method() === 'GET',
            { timeout: 30000 },
        );
        await page.goto('/reports/stock-adjustment', {
            waitUntil: 'domcontentloaded',
        });
        await expectResponseOk(await reportPromise);

        await page.getByRole('button', { name: /filter/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        await expect(
            filtersDialog.getByText('All types', { exact: true }),
        ).toHaveCount(0);
    });

    test('pipeline dashboard labels keep the English text (locale en)', async ({
        page,
    }) => {
        await page.addInitScript((k) => {
            localStorage.setItem(k, 'en');
        }, LOCALE_KEY);

        await login(page);

        const dataPromise = page.waitForResponse(
            (response) =>
                response.url().includes('/api/pipeline-dashboard/data') &&
                response.request().method() === 'GET',
            { timeout: 15000 },
        );
        await page.goto('/pipeline-dashboard', {
            waitUntil: 'domcontentloaded',
        });
        await expectResponseOk(await dataPromise);

        await expect(
            page.getByText('Select Pipeline', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Stale Threshold', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('State Distribution', { exact: true }),
        ).toBeVisible();
        await expect(
            page.getByText('Stale Entities', { exact: true }),
        ).toBeVisible();
    });
});
