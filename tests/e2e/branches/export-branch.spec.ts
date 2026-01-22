import { test, expect } from '@playwright/test';
import { login, createBranch } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export branches to Excel works correctly', async ({ page }) => {
  await login(page);
  await createBranch(page);
  await page.goto('/branches');

  const exportBtn = page.getByRole('button', { name: /Export/i });
  await expect(exportBtn).toBeVisible();

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

  const header = fs.readFileSync(destPath).slice(0, 2).toString('utf8');
  expect(header).toBe('PK');
});
