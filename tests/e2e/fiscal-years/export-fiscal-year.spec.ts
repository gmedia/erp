import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('export fiscal years to excel', async ({ page }) => {
  await login(page);
  await page.goto('/fiscal-years');

  const exportButton = page.getByRole('button', { name: /Export/i });
  await expect(exportButton).toBeVisible();
  
  // Intercept the download
  const downloadPromise = page.waitForEvent('download');
  await exportButton.click();
  const download = await downloadPromise;

  expect(download.suggestedFilename()).toContain('fiscal_years_export');
});
