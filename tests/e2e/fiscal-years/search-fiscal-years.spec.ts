import { test, expect } from '@playwright/test';
import { createFiscalYear, searchFiscalYear } from '../helpers';

test.describe('Fiscal Year Search', () => {
    test('should search fiscal years', async ({ page }) => {
        // Create a unique fiscal year to search for
        const name = await createFiscalYear(page);

        // Perform search using helper
        await searchFiscalYear(page, name);

        // Verify results
        const row = page.locator('tr', { hasText: name }).first();
        await expect(row).toBeVisible();
    });
});
