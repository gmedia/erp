import { test, expect } from '@playwright/test';
import { login, createAssetCategory } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';
import ExcelJS from 'exceljs';

test('export asset categories to Excel', async ({ page }) => {
  await login(page);
  // Ensure at least one category exists
  await createAssetCategory(page, { name: 'Export Test Category' });
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

  // Verify Excel columns
  const workbook = new ExcelJS.Workbook();
  await workbook.xlsx.readFile(destPath);
  const worksheet = workbook.worksheets[0];
  const headers = worksheet.getRow(1).values as string[];

  const expectedHeaders = [
    'ID',
    'Code',
    'Name',
    'Default Useful Life (Months)',
    'Created At',
    'Updated At',
  ];

  for (const header of expectedHeaders) {
    expect(headers).toContain(header);
  }
});
