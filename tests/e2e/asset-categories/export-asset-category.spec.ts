import { test, expect } from '@playwright/test';
import { login, createAssetCategory } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export asset categories to Excel', async ({ page }) => {
  await login(page);
  await createAssetCategory(page);
  await page.goto('/asset-categories');

  const exportBtn = page.getByRole('button', { name: /Export/i });
  await expect(exportBtn).toBeVisible();

  const [download] = await Promise.all([
    page.waitForEvent('download'),
    exportBtn.click(),
  ]);

  const downloadsDir = path.resolve('tests/test-results', 'downloads');
  if (!fs.existsSync(downloadsDir)) {
    fs.mkdirSync(downloadsDir, { recursive: true });
  }
  const destPath = path.join(downloadsDir, download.suggestedFilename());
  await download.saveAs(destPath);

  expect(download.suggestedFilename()).toMatch(/\.xlsx$/i);
  expect(fs.existsSync(destPath)).toBeTruthy();

  const fileBuffer = fs.readFileSync(destPath);
  expect(fileBuffer.length).toBeGreaterThan(0);
});
