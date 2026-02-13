import { test, expect } from '@playwright/test';
import { createAssetLocation, login } from '../helpers';

test('export asset locations', async ({ page }) => {
  await createAssetLocation(page);

  await login(page);
  await page.goto('/asset-locations');

  // Click the export button
  const exportButton = page.getByRole('button', { name: /Export/i });
  await expect(exportButton).toBeVisible();
  
  // Intercept the download or wait for the toast
  const [download] = await Promise.all([
    page.waitForEvent('download').catch(() => null), // Some systems might trigger a download
    exportButton.click(),
  ]);

  if (download) {
      expect(download.suggestedFilename()).toContain('asset_locations_export');
  } else {
      // If no download event, look for toast
      const toast = page.locator('[data-sonner-toast]');
      await expect(toast).toBeVisible({ timeout: 10000 });
      await expect(toast).toContainText(/Export|Success|Downloading/i);
  }
});
