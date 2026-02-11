import { test, expect } from '@playwright/test';
import { createCoaVersion, searchCoaVersion } from '../helpers';

test.describe('COA Version Search', () => {
    test('should search coa versions', async ({ page }) => {
        const timestamp = Date.now();
        const uniqueName = `SearchTest-${timestamp}`;
        await createCoaVersion(page, { name: uniqueName });

        await searchCoaVersion(page, uniqueName);

        const row = page.locator('tr', { hasText: uniqueName }).first();
        await expect(row).toBeVisible();
        
        // Ensure other rows are filtered out (optional but good)
        // This is hard to guarantee unless we know the state, but checking the specific one exists is the requirements.
    });
});
