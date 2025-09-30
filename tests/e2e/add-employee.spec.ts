import { test, expect } from '@playwright/test';
import { login } from './helpers';

test('add new employee end‑to‑end', async ({ page }) => {
  // 1️⃣ Authenticate
  await login(page);

  // 2️⃣ Navigate to employee list page
  await page.goto('/employees');

  // 3️⃣ Open the “Add Employee” dialog
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  // 4️⃣ Fill the form fields
  const timestamp = Date.now();
  const uniqueEmail = `john.doe+${timestamp}@example.com`;

  await page.fill('input[name=\"name\"]', 'John Doe');
  await page.fill('input[name=\"email\"]', uniqueEmail);
  await page.fill('input[name=\"phone\"]', '+628123456789');
  await page.fill('input[name=\"salary\"]', '5000');

  // Department select
  await page.click('button:has-text("Select a department")');
  await page.getByRole('option', { name: 'Engineering' }).click();

  // Position select
  await page.click('button:has-text("Select a position")');
  await page.getByRole('option', { name: 'Senior' }).click();

  // Ensure any modal overlay/backdrop is removed before submitting
  // 5️⃣ Submit the form
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', { state: 'detached' });
  const dialog = page.getByRole('dialog');
  const submitButton = dialog.getByRole('button', { name: /Add/ });
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  // 6️⃣ Search for the newly created employee
  await page.fill('input[placeholder=\"Search employees...\"]', uniqueEmail);
  await page.press('input[placeholder=\"Search employees...\"]', 'Enter');

  // 7️⃣ Assert the employee appears in the table
  const row = page.locator(`text=${uniqueEmail}`);
  await expect(row).toBeVisible();
});
