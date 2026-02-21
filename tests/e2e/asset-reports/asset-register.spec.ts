import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Asset Register Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view and filter asset register report', async ({ page }) => {
        await page.goto('/reports/assets/register');

        await expect(page).toHaveTitle(/Asset Register Report/);

        // Check if data table is visible
        await expect(page.locator('table')).toBeVisible();

        // Check for a generic search input
        const searchInput = page.getByPlaceholder(/Search/i).first();
        await expect(searchInput).toBeVisible();
    });
});
