import { test, expect } from '@playwright/test';
import { login, createAssetModel } from '../helpers';

test.describe('Asset Models - Sorting', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should sort by model name', async ({ page }) => {
        // Create 2 models for sorting
        await createAssetModel(page, { model_name: 'AAA Model', manufacturer: 'Mfg A' });
        await createAssetModel(page, { model_name: 'ZZZ Model', manufacturer: 'Mfg Z' });

        await expect(page.locator('table')).toBeVisible();

        const header = page.getByRole('button', { name: 'Model Name' });
        
        // Click for ASC
        const ascResponse = page.waitForResponse(resp => 
            resp.url().includes('/api/asset-models') && 
            resp.url().includes('sort_by=model_name') && 
            resp.status() === 200
        );
        await header.click();
        await ascResponse;

        // Verify ASC: AAA Model should be first 
        const rows = page.locator('tbody tr');
        await expect(rows.first()).toContainText('AAA Model');
        
        // Click again for DESC
        const descResponse = page.waitForResponse(resp => 
            resp.url().includes('/api/asset-models') && 
            (resp.url().includes('sort_direction=desc') || resp.url().includes('sort_direction=DESC')) && 
            resp.status() === 200
        );
        await header.click();
        await descResponse;

        // Verify DESC: ZZZ Model should be first
        await expect(rows.first()).toContainText('ZZZ Model');
    });
});
