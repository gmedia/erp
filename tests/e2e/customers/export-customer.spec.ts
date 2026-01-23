import { test, expect } from '@playwright/test';
import { createCustomer } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export customers to Excel works correctly', async ({ page }) => {
  // Create a customer to ensure data exists
  await createCustomer(page);
  
  // Navigate back to customers list
  await page.goto('/customers');

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

  // Validate file
  expect(download.suggestedFilename()).toMatch(/customers_export_.*\.xlsx$/i);
  expect(fs.existsSync(destPath)).toBeTruthy();

  // Basic check of file content (zip header)
  const header = fs.readFileSync(destPath).slice(0, 2).toString('utf8');
  expect(header).toBe('PK');
});
