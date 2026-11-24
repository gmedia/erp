import { expect, test } from '@playwright/test';
import { login } from '../helpers';

test.describe('Visual #440 wave 8 mobile fixes', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('users 390: email tidak tertutup pita pudar, aksi sticky', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const usersResponse = page.waitForResponse(
            (r) => r.url().includes('/api/users') && r.status() < 400,
        );
        await page.goto('/users');
        await usersResponse;

        const row = page.getByTestId('user-row').first();
        await expect(row).toBeVisible();

        const actionCell = row.locator('td').last();
        const position = await actionCell.evaluate(
            (el) => getComputedStyle(el).position,
        );
        expect(position).toBe('sticky');
    });

    test('trial-balance 390: kolom Credit tidak tertutup fade tepi', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const reportResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/reports/trial-balance') &&
                r.status() < 400,
        );
        await page.goto('/reports/trial-balance');
        await reportResponse;

        const container = page.locator('[data-slot="table-container"]').first();
        await expect(container).toBeVisible();
        const fade = await container.evaluate(
            (el) => getComputedStyle(el, '::after').backgroundImage,
        );
        expect(fade).toBe('none');
    });

    test('financial-dashboard 390: nilai KPI tidak terpotong kartu', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 390, height: 844 });
        const summaryResponse = page.waitForResponse(
            (r) =>
                r.url().includes('/api/financial-dashboard') &&
                r.status() < 400,
        );
        await page.goto('/financial-dashboard');
        await summaryResponse;

        const values = page.locator('.tabular-nums.font-semibold').filter({
            hasText: 'Rp',
        });
        const count = await values.count();
        test.skip(count === 0, 'Tidak ada nilai KPI di data seed');

        for (let i = 0; i < count; i++) {
            const overflow = await values.nth(i).evaluate((el) => {
                const card = el.closest('[data-slot="card"]');
                if (!card) return false;
                return el.scrollWidth > card.clientWidth;
            });
            expect(overflow).toBe(false);
        }
    });
});
