import { test, expect } from '@playwright/test';
import { createUnit, login } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test.describe('Unit Management - Export', () => {
  test('should export units to Excel', async ({ page }) => {
    await createUnit(page);
    await page.goto('/units');

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
