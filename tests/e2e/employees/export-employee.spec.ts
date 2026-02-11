import { test, expect } from '@playwright/test';
import { login, createEmployee } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export employees to Excel works correctly', async ({ page }) => {
  await login(page);
  await createEmployee(page);
  await page.goto('/employees');

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

  // Basic check for Excel file signature
  const buffer = fs.readFileSync(destPath);
  // Zip-based formats (like .xlsx) start with PK (0x50 0x4B)
  expect(buffer[0]).toBe(0x50);
  expect(buffer[1]).toBe(0x4B);
});
