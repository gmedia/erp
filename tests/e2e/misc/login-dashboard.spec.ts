import { test, expect } from '@playwright/test';
import { login } from '../helpers';

test('login and navigate to dashboard', async ({ page }) => {
  await login(page);
  // Verify dashboard heading
  await expect(page.getByText('Total Customer')).toBeVisible({ timeout: 10000 });
});