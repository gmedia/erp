import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('export assets to excel end-to-end', async ({ page }) => {
  await login(page);
  await page.goto('/assets');

  // Wait for the table to load
  await page.waitForSelector('table');

  // Trigger Export
  const exportBtn = page.getByRole('button', { name: /Export/i });
  await expect(exportBtn).toBeVisible();
  
  // Listen for the download event or check for response
  const [download] = await Promise.all([
    page.waitForEvent('download', { timeout: 30000 }).catch(() => null),
    exportBtn.click(),
  ]);

  if (download) {
    expect(download.suggestedFilename()).toContain('assets');
  } else {
    // If it's a JSON response with a URL (like in our Action), check for a link in the UI or a toast
    // In our implementation, it usually returns a JSON with 'url'.
    // Let's check for a success message if download event didn't trigger immediately.
    await expect(page.getByText(/Exported successfully|Download/i)).toBeVisible({ timeout: 10000 });
  }
});
