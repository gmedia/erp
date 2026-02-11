import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Position Export E2E Test', () => {
  test('should export positions to Excel successfully', async ({ page }) => {
    await login(page);
    await page.goto('/positions');
    await page.waitForLoadState('networkidle');

    const exportBtn = page.getByRole('button', { name: /Export/i });
    await expect(exportBtn).toBeVisible();

    const [download] = await Promise.all([
      page.waitForEvent('download'),
      exportBtn.click(),
    ]);

    expect(download.suggestedFilename()).toMatch(/positions.*\.xlsx$/i);
  });
});
