import { test, expect } from '@playwright/test';
import { createSupplierCategory, login } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test.describe('Supplier Category Management - Export', () => {
  test('should export supplier categories to Excel', async ({ page }) => {
    await createSupplierCategory(page);
    await page.goto('/supplier-categories');

    const exportBtn = page.getByRole('button', { name: /Export/i });
    await expect(exportBtn).toBeVisible();

    const [download] = await Promise.all([
      page.waitForEvent('download'),
      exportBtn.click(),
    ]);

    const downloadsDir = path.resolve('test-results', 'downloads');
    if (!fs.existsSync(downloadsDir)) {
      fs.mkdirSync(downloadsDir, { recursive: true });
    }
    const destPath = path.join(downloadsDir, download.suggestedFilename());
    await download.saveAs(destPath);

    expect(download.suggestedFilename()).toMatch(/\.xlsx$/i);
    expect(fs.existsSync(destPath)).toBeTruthy();
  });
});
