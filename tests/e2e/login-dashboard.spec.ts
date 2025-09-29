import { test, expect } from '@playwright/test';

test('login and navigate to dashboard', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'admin@admin.com');
  await page.fill('input[name="password"]', 'password');
  const loginButton = page.locator('button[type="submit"], button[data-testid="login-button"]');
  await loginButton.click();
  await page.waitForURL('**/dashboard');
  // Verify dashboard heading
  await expect(page.locator('a[href="/dashboard"][data-active="true"]')).toBeVisible();
});
