import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('export products to excel', async ({ page }) => {
  await login(page);
  await page.goto('/products');

  // Click Export button
  const exportButton = page.getByRole('button', { name: /Export/i });
  await expect(exportButton).toBeVisible();
  
  // Note: We don't verify the actual file content here, just the UI flow
  // until the export trigger/success notification if any.
  // In our implementation, ExportProductsAction returns a JSON with URL.
  
  await exportButton.click();

  // Expect a download to be triggered or a success message
  // Since our common UI might show a toast or just trigger download
  // Let's check for "Exporting..." or similar if implemented, 
  // or just wait for network activity.
  
  // Usually, we can use page.waitForEvent('download')
  const downloadPromise = page.waitForEvent('download');
  await exportButton.click();
  const download = await downloadPromise;
  
  expect(download.suggestedFilename()).toContain('product');
});
