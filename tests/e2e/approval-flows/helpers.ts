import { Page, expect } from '@playwright/test';
import * as fs from 'node:fs';

async function clickDropdownOption(
  page: Page,
  text: string | RegExp,
): Promise<void> {
  const matcher = text instanceof RegExp ? text : new RegExp(text, 'i');
  const option = page
    .locator('[role="option"]:visible, ul[aria-busy]:visible button')
    .filter({ hasText: matcher })
    .first();

  await expect(option).toBeVisible();
  await option.click({ force: true });
}

export async function createApprovalFlow(
  page: Page,
  overrides: Record<string, string> = {}
): Promise<string> {
  page.on('pageerror', err => console.log('PAGE ERROR:', err.message));

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
  await clickDropdownOption(page, 'Asset Movement');

  // Status
  const statusCombobox = page.getByRole('combobox', { name: /Status/i });
  await expect(statusCombobox).toBeVisible();
  await statusCombobox.click();
  await clickDropdownOption(page, /^Active$/i);

  // Description
  await page.fill('textarea[name="description"]', 'E2E testing description');

  const dialog = page.getByRole('dialog').first();
  await expect(dialog).toBeVisible();

  const addStepButton = dialog.getByRole('button', { name: /Add Step/i });
  await expect(addStepButton).toBeVisible();
  await addStepButton.click();

  const stepDialog = page.getByRole('dialog', {
    name: /Add Approval Step|Edit Approval Step/i,
  });
  await expect(stepDialog).toBeVisible();

  await stepDialog.locator('input[name="name"]').fill(`Primary Approval ${timestamp}`);

  const approverCombobox = stepDialog.getByRole('combobox', { name: /Approver/i });
  await expect(approverCombobox).toBeVisible();
  await approverCombobox.click();

  const userSearchInput = page.getByPlaceholder('Search...').last();
  await expect(userSearchInput).toBeVisible();
  await userSearchInput.fill('Admin User');
  await page
    .waitForResponse(
      response =>
        response.url().includes('/api/users') &&
        response.request().method() === 'GET' &&
        response.status() < 400,
      { timeout: 10000 },
    )
    .catch(() => null);

  await clickDropdownOption(page, 'Admin User');
  await expect(approverCombobox).toHaveText(/Admin User/);

  const saveStepButton = stepDialog.getByRole('button', { name: /Save Step/i });
  await expect(saveStepButton).toBeVisible();
  await saveStepButton.click();
  await expect(stepDialog).not.toBeVisible({ timeout: 10000 });
  await expect(dialog.getByText(`Primary Approval ${timestamp}`)).toBeVisible();
  
  const submitButton = dialog.locator('button[type="submit"]');
  await expect(submitButton).toBeVisible();
  const createResponsePromise = page.waitForResponse(
    response =>
      response.url().includes('/api/approval-flows') &&
      response.request().method() === 'POST' &&
      response.status() < 400,
    { timeout: 15000 },
  );
  await submitButton.click();

  try {
      await createResponsePromise;
      await expect(dialog).not.toBeVisible({ timeout: 15000 });
  } catch (error) {
      console.error(`Dialog did not close after 15s. Likely validation error or backend failure.`);
      const errorMessages = await dialog
        .locator('.text-destructive, .text-red-500, [role="alert"]')
        .allTextContents()
        .catch(() => []);
      if (errorMessages.length > 0) {
          console.error(`Found error messages in dialog: ${errorMessages.join(', ')}`);
      }
      await page.screenshot({ path: 'test-validation-error.png', fullPage: true });
      throw error;
  }

  return code;
}

export async function searchApprovalFlow(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder(/Search/i).first();
  await expect(searchInput).toBeVisible();
  await searchInput.clear();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  
  // Wait for the API response instead of arbitrary timeout
  await page.waitForResponse(
    r => r.url().includes('/api/approval-flows') && r.url().includes('search=') && r.status() === 200,
    { timeout: 5000 }
  ).catch(() => null);
  
  // Wait for table to update by checking if the loading state finishes or the row appears
  const tableBody = page.locator('tbody');
  await expect(tableBody).toBeVisible();
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
    await clickDropdownOption(page, updates.is_active ? /^Active$/i : /^Inactive$/i);
  }

  const updateBtn = dialog.getByRole('button', { name: /Update|Save|Create|Submit/i });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
  
  await expect(dialog).not.toBeVisible({ timeout: 10000 });
  await page.waitForResponse(r => r.url().includes('/api/approval-flows') && ['PUT', 'PATCH'].includes(r.request().method()) && r.status() < 400).catch(() => null);
}
