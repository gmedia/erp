import { Page, expect } from '@playwright/test';

/**
 * Create a new COA version via the UI.
 */
export async function createCoaVersion(
  page: Page,
  overrides: Partial<{
    name: string;
    fiscal_year_id: string; // This should be the label/name of fiscal year
    status: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now();
  const defaultName = `COA Version ${timestamp}`;

  const addButton = page.getByRole('button', { name: /Add/i });
  await expect(addButton).toBeVisible();
  await addButton.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  const name = overrides.name ?? defaultName;
  await dialog.locator('input[name="name"]').fill(name);

  // Select fiscal year (Regular SelectField)
  const fiscalYearTrigger = dialog.locator('button').filter({ hasText: /Select fiscal year/i });
  await expect(fiscalYearTrigger).toBeVisible();
  await fiscalYearTrigger.click();
  
  const fiscalYearLabel = overrides.fiscal_year_id ?? '2025';
  await page.getByRole('option', { name: fiscalYearLabel }).first().click();

  // Select status (Regular SelectField)
  if (overrides.status) {
    const statusTrigger = dialog.locator('button').filter({ hasText: /Select status|Draft|Active|Archived/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: overrides.status, exact: true }).click();
  }

  const submitButton = dialog.getByRole('button', { name: /Create/i }).last();
  await submitButton.click();

  await expect(dialog).not.toBeVisible({ timeout: 10000 });

  return name;
}

/**
 * Search for a COA version by name.
 */
export async function searchCoaVersion(page: Page, name: string): Promise<void> {
  const searchInput = page.getByPlaceholder('Search COA versions...');
  await searchInput.waitFor({ state: 'visible' });
  await searchInput.fill(name);
  await searchInput.press('Enter');
  // Wait for response to ensure the table has updated
  await page.waitForResponse(r => r.url().includes('/api/coa-versions') && r.status() < 400).catch(() => null);
}

/**
 * Edit an existing COA version.
 */
export async function editCoaVersion(
  page: Page,
  name: string,
  updates: { name?: string; status?: string }
): Promise<void> {
  await searchCoaVersion(page, name);

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
    const statusTrigger = dialog.locator('button').filter({ hasText: /Draft|Active|Archived/i });
    await statusTrigger.click();
    await page.getByRole('option', { name: updates.status, exact: true }).click();
  }

  const updateBtn = dialog.getByRole('button', { name: /Update/i });
  await updateBtn.click();

  await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
