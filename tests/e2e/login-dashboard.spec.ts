import { test, expect } from '@playwright/test';
import { login } from './helpers';

test('login and navigate to dashboard', async ({ page }) => {
  await login(page);
  // Verify dashboard heading
  await expect(page.locator('a[href="/dashboard"][data-active="true"]')).toBeVisible();
});
