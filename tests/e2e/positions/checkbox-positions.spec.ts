import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test.describe('Position Checkbox E2E Test', () => {
  test('should have checkbox in row body but not in row head', async ({ page }) => {
    await login(page);
    await page.goto('/positions');
    await page.waitForLoadState('networkidle');

    // Check row head (thead)
    const headerRow = page.locator('thead tr');
    const headerCheckbox = headerRow.getByRole('checkbox');
    await expect(headerCheckbox).not.toBeVisible();

    // Check row body (tbody)
    const bodyRow = page.locator('tbody tr').first();
    const bodyCheckbox = bodyRow.getByRole('checkbox');
    await expect(bodyCheckbox).toBeVisible();
  });
});
