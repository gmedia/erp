import { test, expect } from '@playwright/test';
import { createEmployee } from './helpers';

test('employee filter functionality', async ({ page }) => {
  // Create two employees with distinct departments and positions
  const email1 = await createEmployee(page, {
    department: 'Engineering',
    position: 'Manager',
  });
  const email2 = await createEmployee(page, {
    department: 'Marketing',
    position: 'Analyst',
  });

  // ---- Search filter ----
  await page.getByRole('button', { name: /Filters/i }).click();
  await page.fill('input[placeholder="Search employees..."]', email1);
  await page.getByRole('button', { name: /Apply Filters/i }).click();
  await expect(page.locator(`text=${email1}`)).toBeVisible();
  await expect(page.locator(`text=${email2}`)).toBeHidden();

  // ---- Department filter ----
  await page.goto('/employees');
  await page.getByRole('button', { name: /Filters/i }).click();
  await page.click('button:has-text("Select a department")');
  await page.getByRole('option', { name: 'Engineering' }).click();
  await page.getByRole('button', { name: /Apply Filters/i }).click();
  await expect(page.locator(`text=${email1}`)).toBeVisible();
  await expect(page.locator(`text=${email2}`)).toBeHidden();

  // ---- Position filter ----
  await page.goto('/employees');
  await page.getByRole('button', { name: /Filters/i }).click();
  await page.click('button:has-text("Select a position")');
  await page.getByRole('option', { name: 'Analyst' }).click();
  await page.getByRole('button', { name: /Apply Filters/i }).click();
  await expect(page.locator(`text=${email2}`)).toBeVisible();
  await expect(page.locator(`text=${email1}`)).toBeHidden();
});