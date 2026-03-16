import { Page, expect } from '@playwright/test';
import { login } from '../helpers';

async function pickAsyncOption(page: Page, value: string): Promise<void> {
  const listbox = page.locator('[role="listbox"]:visible, ul[aria-busy]:visible').last();
  await expect(listbox).toBeVisible({ timeout: 10000 });

  const searchInput = page.locator('input[placeholder="Search..."]:visible').last();
  if (await searchInput.isVisible().catch(() => false)) {
    await searchInput.fill(value);
    await page.waitForTimeout(500);
  }

  const option = page
    .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
    .filter({ hasText: new RegExp(value, 'i') })
    .first();
  await expect(option).toBeVisible({ timeout: 15000 });
  await option.click({ force: true });
}

/**
 * Create a new approval delegation via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override default values.
 * @returns The start date to allow verifying the creation.
 */
export async function createApprovalDelegation(
  page: Page,
  overrides: Partial<{
    delegator_user_id: string; // The username to search
    delegate_user_id: string;  // The username to search
    approvable_type: string;
    start_date: string;
    end_date: string;
    reason: string;
    is_active: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now().toString().slice(-6);
  
  const delegator = overrides.delegator_user_id ?? 'Admin User';
  const delegate = overrides.delegate_user_id ?? 'Test User';
  const type = overrides.approvable_type ?? 'Expense';
  const reason = overrides.reason ?? `Test Reason ${timestamp}`;
  const status = overrides.is_active ?? 'Active';

  await login(page);

  await page.goto('/approval-delegations');

  const addButton = page.getByRole('button', { name: /Add/i });
  try {
    await expect(addButton).toBeVisible({ timeout: 5000 });
  } catch (error) {
    console.error("Add button hidden. Page content:");
    console.error(await page.textContent('body'));
    await page.screenshot({ path: 'tests/e2e/test-results/debug-add-button-ad.png' });
    throw error;
  }
  await addButton.click();

  const dialog = page.getByRole('dialog', {
    name: /Add New Approval Delegation|Edit Approval Delegation/i,
  });
  await expect(dialog).toBeVisible();

  // Handle AsyncSelect for Delegator
  const delegatorTrigger = dialog.getByRole('combobox', { name: /Delegator/i }).first();
  await delegatorTrigger.click();
  await pickAsyncOption(page, delegator);

  // Handle AsyncSelect for Delegate
  const delegateTrigger = dialog.getByRole('combobox', { name: /Delegate/i }).first();
  await delegateTrigger.click();
  await pickAsyncOption(page, delegate);

  // Fill text fields
  await page.fill('input[name="approvable_type"]', type);
  await page.fill('input[name="reason"]', reason);

  // DatePicker fields (start_date, end_date) are pre-filled with today's date by default.
  // We can skip interacting with the calendar popup to avoid Playwright timeouts.

  // Handle Select for Status
  if (status !== 'Active') {
      const statusTrigger = dialog.getByRole('combobox', { name: /Status/i }).first();
      await statusTrigger.click();
      const statusOption = page
        .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
        .filter({ hasText: new RegExp(status, 'i') })
        .first();
      await expect(statusOption).toBeVisible();
      await statusOption.click({ force: true });
  }

  const submitButton = dialog.getByRole('button', { name: /Add|Create|Submit|Buat/i }).first();
  await expect(submitButton).toBeVisible();
  await submitButton.click();

  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return reason;
}

export async function searchApprovalDelegation(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder(/Search/i).first();
  await expect(searchInput).toBeVisible();
  await searchInput.clear();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  
  await page.waitForResponse(r => r.url().includes('/api/approval-delegations') && r.status() === 200).catch(() => null);
  await page.waitForTimeout(500);
}

export async function editApprovalDelegation(
  page: Page,
  reason: string,
  updates: { reason?: string; status?: string }
): Promise<void> {
  await searchApprovalDelegation(page, reason);

  const row = page.locator('tr').filter({ hasText: reason }).first();
  await expect(row).toBeVisible();
  
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await editItem.click();
  
  const dialog = page.getByRole('dialog', {
    name: /Edit Approval Delegation|Add New Approval Delegation/i,
  });
  await expect(dialog).toBeVisible();

  if (updates.reason) {
    await dialog.locator('input[name="reason"]').fill(updates.reason);
  }
  
  if (updates.status) {
    const statusTrigger = dialog.getByRole('combobox', { name: /Status/i }).first();
    await statusTrigger.click();
    const statusOption = page
      .locator('[role="option"]:visible, ul[aria-busy]:visible button:visible')
      .filter({ hasText: new RegExp(updates.status, 'i') })
      .first();
    await expect(statusOption).toBeVisible();
    await statusOption.click({ force: true });
  }

  const updateBtn = dialog.getByRole('button', { name: /Update|Save/i });
  await updateBtn.click();
  
  await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
