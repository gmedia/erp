import { Page, expect } from '@playwright/test';

/**
 * Helper to pick a date from the DatePickerField.
 */
export async function pickDate(
  page: Page,
  label: string,
  day: string
): Promise<void> {
  const trigger = page.getByRole('button', { name: label, exact: true });
  await trigger.waitFor({ state: 'visible' });
  await trigger.click();
  
  const calendar = page.locator('[data-slot="calendar"]').last();
  await calendar.waitFor({ state: 'visible', timeout: 15000 });
  await page.waitForTimeout(500);

  const dayButton = calendar.locator('button').filter({ hasText: new RegExp(`^${day}$`) }).first();
  await dayButton.waitFor({ state: 'visible' });
  await dayButton.click({ force: true });
  
  await page.keyboard.press('Escape');
  await expect(calendar).not.toBeVisible();
}

/**
 * Create a new fiscal year via the UI.
 */
export async function createFiscalYear(
  page: Page,
  overrides: Partial<{
    name: string;
    start_date_day: string;
    end_date_day: string;
    status: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const random = Math.floor(Math.random() * 10000);
  const name = overrides.name ?? `FY ${timestamp}-${random}`;



  const addButton = page.getByRole('button', { name: /Add/i });
  await addButton.waitFor({ state: 'visible' });
  await addButton.click();

  const dialog = page.getByRole('dialog', { name: /Add New Fiscal Year/i });
  await expect(dialog).toBeVisible();

  await dialog.locator('input[name="name"]').fill(name);

  await pickDate(page, 'Start Date', overrides.start_date_day ?? '10');
  await pickDate(page, 'End Date', overrides.end_date_day ?? '20');

  if (overrides.status) {
    const statusTrigger = dialog.locator('button').filter({ hasText: /Select status|Open|Closed|Locked/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: overrides.status, exact: true }).click();
  }

  const submitButton = dialog.getByRole('button', { name: /Add/i }).last();
  await submitButton.click();

  await expect(dialog).not.toBeVisible();

  return name;
}

/**
 * Search for a fiscal year by name.
 */
export async function searchFiscalYear(page: Page, name: string): Promise<void> {
  const searchInput = page.getByPlaceholder('Search fiscal years...');
  await searchInput.waitFor({ state: 'visible' });
  await searchInput.fill(name);
  await searchInput.press('Enter');
  await page.waitForResponse(r => r.url().includes('/api/fiscal-years') && r.status() < 400).catch(() => null);
}

/**
 * Edit an existing fiscal year.
 */
export async function editFiscalYear(
  page: Page,
  name: string,
  updates: { name?: string; status?: string }
): Promise<void> {
  await searchFiscalYear(page, name);

  const row = page.locator('tr', { hasText: name }).first();
  await expect(row).toBeVisible();
  
  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const editItem = page.getByRole('menuitem', { name: /Edit/i });
  await editItem.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  if (updates.name) {
    await dialog.locator('input[name="name"]').fill(updates.name);
  }

  if (updates.status) {
    const statusTrigger = dialog.locator('button', { hasText: /Open|Closed|Locked/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: updates.status, exact: true }).click();
  }

  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await updateBtn.click();

  await expect(dialog).not.toBeVisible();
}
