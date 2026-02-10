import { test, expect } from '@playwright/test';
import { createPosition, searchPosition } from '../helpers';

test.describe('Position Add E2E Test', () => {
  test('should add a new position successfully', async ({ page }) => {
    const name = await createPosition(page);
    await searchPosition(page, name);
    
    const row = page.locator('tr').filter({ hasText: name }).first();
    await expect(row).toBeVisible();
  });
});
