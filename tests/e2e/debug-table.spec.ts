import { test, expect } from '@playwright/test';
import { login } from './helpers';

test('debug table structure', async ({ page }) => {
    await login(page);
    await page.goto('/asset-models');
    await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10000 });
    
    const headers = await page.locator('thead th').allInnerTexts();
    console.log('Headers:', JSON.stringify(headers));
    
    const rows = await page.locator('tbody tr').all();
    for (let i = 0; i < Math.min(rows.length, 3); i++) {
        const cells = await rows[i].locator('td').allInnerTexts();
        console.log(`Row ${i} cells:`, JSON.stringify(cells));
    }
});
