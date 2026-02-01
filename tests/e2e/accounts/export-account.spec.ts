import { test, expect } from '@playwright/test';
import { login, createAccount } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export accounts to Excel works correctly', async ({ page }) => {
  test.setTimeout(120000);
  await login(page);
  await createAccount(page, {
    coa_version: 'COA 2026 Enhanced',
    name: 'Export Test Account',
  });
  
  await page.goto('/accounts');

  // The export button is next to the COA version selector and has a download icon
  const exportBtn = page.locator('button').filter({ has: page.locator('svg.lucide-download') });
  await expect(exportBtn).toBeVisible({ timeout: 15000 });

  const [download] = await Promise.all([
    page.waitForEvent('download'),
    exportBtn.click(),
  ]);

  const downloadsDir = path.resolve('e2e/test-results', 'downloads');
  if (!fs.existsSync(downloadsDir)) {
    fs.mkdirSync(downloadsDir, { recursive: true });
  }
  const destPath = path.join(downloadsDir, download.suggestedFilename());
  await download.saveAs(destPath);

  expect(download.suggestedFilename()).toMatch(/\.xlsx$/i);
  expect(fs.existsSync(destPath)).toBeTruthy();
});
