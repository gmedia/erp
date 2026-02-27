import { Page, expect } from '@playwright/test';
import { login } from '../helpers';
import * as fs from 'fs';

export async function createApprovalFlow(
  page: Page,
  overrides: Record<string, string> = {}
): Promise<string> {
  await login(page);

  page.on('console', msg => console.log('PAGE LOG:', msg.text()));
  page.on('pageerror', err => console.log('PAGE ERROR:', err.message));

  await page.goto('/approval-flows');

  const addButton = page.getByRole('button', { name: /Add/i });
  try {
    await expect(addButton).toBeVisible({ timeout: 5000 });
  } catch (error) {
    console.error('Add button not found. Page URL:', page.url());
    fs.writeFileSync('./debug-page.html', await page.content());
    console.log('Saved page content to ./debug-page.html');
    throw error;
  }
  await addButton.click();

  const timestamp = Date.now().toString().slice(-6);
  const defaultCode = `flow_${timestamp}`;
  const code = overrides.code ?? defaultCode;

  // Basic info
  await page.fill('input[name="name"]', overrides.name ?? `Test Flow ${timestamp}`);
  await page.fill('input[name="code"]', code);

  // Approvable type
  const typeCombobox = page.getByRole('combobox', { name: /Approvable Type/i });
  await expect(typeCombobox).toBeVisible();
  await typeCombobox.click();
  await page.getByRole('option', { name: 'Asset Movement' }).click();

  // Status
  const statusCombobox = page.getByRole('combobox', { name: /Status/i });
  await expect(statusCombobox).toBeVisible();
  await statusCombobox.click();
  await page.getByRole('option', { name: 'Active', exact: true }).click();

  // Description
  await page.fill('textarea[name="description"]', 'E2E testing description');

  // Dialog interaction loop for nested steps
  await page.fill('input[name="steps.0.name"]', 'Manager Review');
  
  const stepActionCombobox = page.getByRole('combobox', { name: /Required Action/i }).first();
  await expect(stepActionCombobox).toBeVisible();
  await stepActionCombobox.click();
  await page.getByRole('option', { name: 'Review', exact: true }).click();

  // Approver type (Specific Role for easier E2E execution without selecting dynamic comboboxes)
  const approverTypeCombobox = page.getByRole('combobox', { name: /Approver Type/i }).first();
  await approverTypeCombobox.click();
  await page.getByRole('option', { name: 'Specific Role', exact: true }).click({ force: true });
  await page.fill('input[name="steps.0.approver_role_id"]', '1');

  // Ensure the dialog is visible before interacting
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();
  await page.waitForTimeout(500); // Wait for dialog animation
  const submitButton = dialog.locator('button[type="submit"]');
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  try {
      await expect(dialog).not.toBeVisible({ timeout: 15000 });
  } catch (error) {
      console.error(`Dialog did not close after 15s. Likely validation error or backend failure.`);
      await page.screenshot({ path: 'test-validation-error.png', fullPage: true });
      throw error;
  }

  return code;
}

export async function searchApprovalFlow(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder('Search...');
  await searchInput.clear();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  await page.waitForTimeout(500); // Wait for debounce
  await page.waitForResponse(r => r.url().includes('/api/approval-flows') && r.status() === 200).catch(() => null);
  await page.waitForTimeout(500);
}

export async function editApprovalFlow(
  page: Page,
  code: string,
  updates: { name?: string; is_active?: boolean }
): Promise<void> {
  await searchApprovalFlow(page, code);

  const row = page.locator('tr').filter({ hasText: code }).first();
  await expect(row).toBeVisible();
  
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await expect(actionsBtn).toBeVisible();
  await actionsBtn.click();

  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await expect(editItem).toBeVisible();
  await editItem.click();
  
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  if (updates.name) {
    const nameInput = dialog.locator('input[name="name"]');
    await nameInput.clear();
    await nameInput.fill(updates.name);
  }
  
  if (updates.is_active !== undefined) {
    const statusTrigger = dialog.getByRole('combobox', { name: 'Status' });
    await statusTrigger.click();
    await page.getByRole('option', { name: updates.is_active ? 'Active' : 'Inactive', exact: true }).click();
  }

  const updateBtn = dialog.getByRole('button', { name: /Update|Save|Create|Submit/i });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
  
  await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
