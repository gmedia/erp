import { expect, test } from '@playwright/test';
import { expectResponseOk, login } from '../helpers';

test.describe('Stock Movement Report', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
    });

    test('can view report, filter period, and export', async ({ page }) => {
        const initialResponsePromise = page.waitForResponse(
            (r) => r.url().includes('/api/reports/stock-movement'),
            { timeout: 30000 },
        );

        await page.goto('/reports/stock-movement');
        await page.waitForURL('**/reports/stock-movement', { timeout: 15000 });
        await expectResponseOk(await initialResponsePromise);

        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();

        const [sortResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/stock-movement') &&
                    r.url().includes('sort_by=product_category_name'),
                { timeout: 30000 },
            ),
            page.getByRole('button', { name: 'Category', exact: true }).click(),
        ]);
        await expectResponseOk(sortResponse);

        const [exportResponse] = await Promise.all([
            page.waitForResponse(
                (r) => r.url().includes('/api/reports/stock-movement/export'),
                { timeout: 30000 },
            ),
            page.getByRole('button', { name: /export/i }).click(),
        ]);
        await expectResponseOk(exportResponse);

        await page.getByRole('button', { name: /filters/i }).click();
        const filtersDialog = page.getByRole('dialog');
        await expect(filtersDialog).toBeVisible();

        const categoryContainer = filtersDialog
            .locator('label', { hasText: /^Category$/ })
            .locator('..');
        const categoryFilter = categoryContainer.locator('button').first();
        await categoryFilter.click();

        const categoryOption = page
            .locator(
                '[role="option"]:visible, ul[aria-busy]:visible button:visible',
            )
            .filter({ hasNotText: /All categories/i })
            .first();
        await expect(categoryOption).toBeVisible({ timeout: 10000 });

        const selectedCategory = (await categoryOption.innerText()).trim();
        await categoryOption.click({ force: true });
        await expect(categoryFilter).toContainText(selectedCategory);

        const [filterResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/api/reports/stock-movement') &&
                    r.url().includes('category_id='),
                { timeout: 30000 },
            ),
            filtersDialog
                .getByRole('button', { name: 'Apply Filters' })
                .click(),
        ]);
        await expectResponseOk(filterResponse);
    });
});
