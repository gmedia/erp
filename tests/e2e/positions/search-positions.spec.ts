import { test, expect } from '@playwright/test';
import { createPosition, searchPosition } from '../helpers';

test.describe('Position Search E2E Test', () => {
  test('should search positions in main search bar successfully', async ({ page }) => {
    const name = await createPosition(page);
    await searchPosition(page, name);
    
    await expect(page.locator('tr').filter({ hasText: name }).first()).toBeVisible();
  });
});
