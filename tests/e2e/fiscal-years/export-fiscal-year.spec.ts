import { test, expect } from '@playwright/test';
import { login, createFiscalYear } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export fiscal years to excel', async ({ page }) => {
  await login(page);
  // Ensure data exists
  await createFiscalYear(page);
  
  await page.goto('/fiscal-years');

  const exportButton = page.getByRole('button', { name: /Export/i });
  await expect(exportButton).toBeVisible();
  
  // Intercept the download
  const [download] = await Promise.all([
    page.waitForEvent('download'),
    exportButton.click(),
  ]);

  const downloadsDir = path.resolve('tests/e2e/test-results', 'downloads');
  if (!fs.existsSync(downloadsDir)) {
    fs.mkdirSync(downloadsDir, { recursive: true });
  }
  const destPath = path.join(downloadsDir, download.suggestedFilename());
  await download.saveAs(destPath);

  // Verify filename
  expect(download.suggestedFilename()).toContain('fiscal_years');
  expect(download.suggestedFilename()).toMatch(/\.xlsx$/i);

  // Verify file exists
  expect(fs.existsSync(destPath)).toBeTruthy();

  // Basic check that it's a zip file (Excel files are zipped XMLs)
  const header = fs.readFileSync(destPath).slice(0, 2).toString('utf8');
  expect(header).toBe('PK');
});
