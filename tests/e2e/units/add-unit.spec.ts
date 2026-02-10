import { test, expect } from '@playwright/test';
import { createUnit, login } from '../helpers';

test.describe('Unit Management - Add', () => {
  test('should add a new unit successfully', async ({ page }) => {
    const name = await createUnit(page);
    
    await login(page);
    await page.goto('/units');
    
    const searchInput = page.getByPlaceholder(/Search units.../i);
    await searchInput.fill(name);
    await searchInput.press('Enter');

    await expect(page.locator(`tr:has-text("${name}")`)).toBeVisible();
  });
});
