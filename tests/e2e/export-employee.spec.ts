import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import { createEmployee } from './helpers';

test('Export employee CSV', async ({ page }, testInfo) => {
  // Create an employee to ensure there is data to export
  const email = await createEmployee(page);

  // Click the Export button and wait for the download to start
  const [download] = await Promise.all([
    page.waitForEvent('download'),
    page.getByRole('button', { name: /Export/i }).click(),
  ]);

  // Save the downloaded file to the test output directory
  const savePath = testInfo.outputPath(`employee-${Date.now()}.csv`);
  await download.saveAs(savePath);

  // Verify the file has a .csv extension
  expect(savePath).toMatch(/\.csv$/);

  // Read the CSV content and verify it includes the created employee's email
  const content = fs.readFileSync(savePath, 'utf8');
  expect(content).toContain(email);
});
