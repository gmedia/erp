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

  // Intercept the export request
  const exportPromise = page.waitForResponse(response => 
    response.url().includes('/api/suppliers/export') && response.status() === 200
  );

  await exportBtn.click();

  const response = await exportPromise;
  const jsonResponse = await response.json();

  expect(jsonResponse.url).toBeTruthy();
  expect(jsonResponse.filename).toMatch(/suppliers_.*\.xlsx$/i);

  // Setup download path
  const downloadsDir = path.resolve('e2e/test-results', 'downloads');
  if (!fs.existsSync(downloadsDir)) {
    fs.mkdirSync(downloadsDir, { recursive: true });
  }
  const destPath = path.join(downloadsDir, jsonResponse.filename);

  // Download the file manually using the URL from the response
  const fileResponse = await page.request.get(jsonResponse.url);
  expect(fileResponse.ok()).toBeTruthy();
  const fileBuffer = await fileResponse.body();
  
  fs.writeFileSync(destPath, fileBuffer);

  // Validate file existence
  expect(fs.existsSync(destPath)).toBeTruthy();

  // Basic check of file content (zip header for xlsx)
  const header = fs.readFileSync(destPath).slice(0, 2).toString('utf8');
  expect(header).toBe('PK');
});
