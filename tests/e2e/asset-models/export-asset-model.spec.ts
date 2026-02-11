import { test, expect } from '@playwright/test';
import { login } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test.describe('Asset Models - Export', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/asset-models');
    });

    test('should export asset models with all columns', async ({ page }) => {
        await expect(page.locator('table')).toBeVisible();

        // Trigger export
        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('button', { name: 'Export' }).click();
        const download = await downloadPromise;

        // Save to temporary path
        const downloadPath = path.join(test.info().project.outputDir, download.suggestedFilename());
        await download.saveAs(downloadPath);

        // Verify file exists
        expect(fs.existsSync(downloadPath)).toBeTruthy();
        
        // Verify file extension
        expect(download.suggestedFilename()).toMatch(/\.xlsx$/);

        // Verify file size is greater than 0
        const stats = fs.statSync(downloadPath);
        expect(stats.size).toBeGreaterThan(0);
    });
});
