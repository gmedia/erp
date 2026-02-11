import { test, expect } from '@playwright/test';
import { createPosition, editPosition } from '../helpers';

test.describe('Position Edit E2E Test', () => {
  test('should edit an existing position successfully', async ({ page }) => {
    const name = await createPosition(page);
    const updatedName = `${name} Updated`;
    
    await editPosition(page, name, { name: updatedName });
    
    await expect(page.locator(`text=${updatedName}`)).toBeVisible();
  });
});
