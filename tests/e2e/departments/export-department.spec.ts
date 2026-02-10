import { test, expect } from '@playwright/test';
import { createDepartment } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';

test('export departments to Excel works correctly', async ({ page }) => {
  await createDepartment(page);
  await page.goto('/departments');

  const exportBtn = page.getByRole('button', { name: /Export/i });
  await expect(exportBtn).toBeVisible();

  const exportPromise = page.waitForResponse(response => 
    response.url().includes('/api/departments/export') && response.status() === 200
  );

  await exportBtn.click();

  const response = await exportPromise;
  const jsonResponse = await response.json();

  expect(jsonResponse.url).toBeTruthy();

  const downloadsDir = path.resolve('tests/e2e/test-results', 'downloads');
  if (!fs.existsSync(downloadsDir)) {
    fs.mkdirSync(downloadsDir, { recursive: true });
  }
  const destPath = path.join(downloadsDir, jsonResponse.filename);

  const fileResponse = await page.request.get(jsonResponse.url);
  expect(fileResponse.ok()).toBeTruthy();
  const fileBuffer = await fileResponse.body();
  fs.writeFileSync(destPath, fileBuffer);

  expect(fs.existsSync(destPath)).toBeTruthy();
  const header = fs.readFileSync(destPath).slice(0, 2).toString('utf8');
  expect(header).toBe('PK');
});
