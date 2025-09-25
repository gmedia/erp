import { test, expect } from '@playwright/test';

test('basic sanity check', async ({ page }) => {
  await page.goto('about:blank');
  expect(true).toBeTruthy();
});