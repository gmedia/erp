import { test, expect } from '@playwright/test';
import { createSupplier } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export suppliers to Excel works correctly', async ({ page }) => {
  // Create a supplier to ensure data exists
  await createSupplier(page);
  
  // Navigate back to suppliers list
  await page.goto('/suppliers');

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
  expect(download.suggestedFilename()).toMatch(/suppliers_export_.*\.xlsx$/i); // Or just suppliers_.*\.xlsx
  // Check ExportSuppliersAction for filename prefix. It is "suppliers_".
  expect(fs.existsSync(destPath)).toBeTruthy();

  // Basic check of file content (zip header)
  const header = fs.readFileSync(destPath).slice(0, 2).toString('utf8');
  expect(header).toBe('PK');
});
