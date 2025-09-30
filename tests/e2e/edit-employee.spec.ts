import { test, expect } from '@playwright/test';
import { login } from './helpers';

test('edit employee end‑to‑end', async ({ page }) => {
  // 1️⃣ Authenticate
  await login(page);

  // 2️⃣ Navigate to employee list page
  await page.goto('/employees');

  // 3️⃣ Create a new employee (reuse add flow)
  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  const timestamp = Date.now();
  const uniqueEmail = `john.doe+${timestamp}@example.com`;

  await page.fill('input[name="name"]', 'John Doe');
  await page.fill('input[name="email"]', uniqueEmail);
  await page.fill('input[name="phone"]', '+628123456789');
  await page.fill('input[name="salary"]', '5000');

  // Department select
  await page.click('button:has-text("Select a department")');
  await page.getByRole('option', { name: 'Engineering' }).click();

  // Position select
  await page.click('button:has-text("Select a position")');
  await page.getByRole('option', { name: 'Senior' }).click();

  // Ensure any modal overlay/backdrop is removed before submitting
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', { state: 'detached' });
  const addDialog = page.getByRole('dialog');
  const addSubmit = addDialog.getByRole('button', { name: /Add/ });
  await expect(addSubmit).toBeVisible();
  await addSubmit.click();

  // 4️⃣ Search for the newly created employee
  await page.fill('input[placeholder="Search employees..."]', uniqueEmail);
  await page.press('input[placeholder="Search employees..."]', 'Enter');

  // 5️⃣ Click edit for that employee
  // Locate the table row that contains the unique email and then find the Actions button within that row.
  const row = page.locator('tr', { hasText: uniqueEmail }).first();
  await expect(row).toBeVisible();
  // Ensure the row is attached and stable before interacting.
  await row.waitFor({ state: 'attached' });
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click({ force: true });
  // Locate the Edit menu item (rendered as a checkbox item in the dropdown)
  const editItem = page.getByRole('menuitemcheckbox', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click({ force: true });

  // 6️⃣ Modify fields in edit dialog
  await page.fill('input[name="name"]', 'John Doe Updated');
  await page.fill('input[name="salary"]', '6000');

  // Ensure overlay detached before submitting edit
  await page.waitForSelector('.fixed.inset-0.bg-black\\/50', { state: 'detached' });
  const editDialog = page.getByRole('dialog');
  const updateBtn = editDialog.getByRole('button', { name: /Update/ });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();

  // 7️⃣ Verify updated data appears
  await page.fill('input[placeholder="Search employees..."]', uniqueEmail);
  await page.press('input[placeholder="Search employees..."]', 'Enter');
  await expect(page.locator('text=John Doe Updated')).toBeVisible();
});