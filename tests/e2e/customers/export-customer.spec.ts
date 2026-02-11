import { test, expect } from '@playwright/test';
import { createCustomer } from '../helpers';
import * as fs from 'fs';
import * as path from 'path';
import ExcelJS from 'exceljs';

test('export customers and verify all columns', async ({ page }) => {
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

  // Validate file exists
  expect(fs.existsSync(destPath)).toBeTruthy();

  // Verify columns using ExcelJS
  const workbook = new ExcelJS.Workbook();
  await workbook.xlsx.readFile(destPath);
  const worksheet = workbook.getWorksheet(1);
  const headerRow = worksheet.getRow(1);
  
  const expectedColumns = [
    'ID',
    'Name',
    'Email',
    'Phone',
    'Address',
    'Branch',
    'Category',
    'Status',
    'Notes',
    'Created At',
  ];

  const actualColumns = headerRow.values as string[];
  // ExcelJS headerRow.values is 1-indexed, first element is null or empty
  const cleanActualColumns = actualColumns.filter(v => v !== null && v !== undefined && v !== '');

  for (const expected of expectedColumns) {
    expect(cleanActualColumns).toContain(expected);
  }
});
