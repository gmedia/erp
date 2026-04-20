import { Page, expect } from '@playwright/test';

export async function createAssetMaintenance(
  page: Page,
  overrides: Partial<{
    notes: string;
    cost: string;
  }> = {}
): Promise<string> {
  await page.goto('/asset-maintenances');

  await page.getByRole('button', { name: /Add/i }).click();
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();

  const assetTrigger = dialog.locator('button').filter({ hasText: /Select asset/i });
  await assetTrigger.click();
  const assetOption = page.locator('[role="option"]:visible, ul[aria-busy]:visible button:visible').first();
  await expect(assetOption).toBeVisible();
  await assetOption.click({ force: true });

  const typeTrigger = dialog.locator('button').filter({ hasText: /Preventive|Corrective|Calibration|Other/i }).first();
  await typeTrigger.click();
  await page.getByRole('option', { name: /Preventive/i }).first().click();

  const statusTrigger = dialog.locator('button').filter({ hasText: /Scheduled|In Progress|Completed|Cancelled/i }).first();
  await statusTrigger.click();
  await page.getByRole('option', { name: /^Scheduled$/i }).first().click();

  const identifier = overrides.notes ?? `MAINT-${Date.now()}`;
  await dialog.locator('textarea[name="notes"]').fill(identifier);

  if (overrides.cost) {
    await dialog.locator('input[name="cost"]').fill(overrides.cost);
  }

  const submitBtn = dialog.getByRole('button', { name: /Save Maintenance|Update Maintenance|Add|Create|Submit/i }).last();
  const mutationResponsePromise = page.waitForResponse(
    response =>
      response.url().includes('/api/asset-maintenances') &&
      ['POST', 'PUT', 'PATCH'].includes(response.request().method()) &&
      response.status() < 400,
    { timeout: 15000 },
  );
  await submitBtn.click();
  await mutationResponsePromise;

  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return identifier;
}

export async function searchAssetMaintenance(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder(/Search/i).first();
  await expect(searchInput).toBeVisible();
  const normalizedQuery = query.trim();
  if ((await searchInput.inputValue()).trim() === normalizedQuery) {
    return;
  }

  const responsePromise = page.waitForResponse(
    response =>
      response.url().includes('/api/asset-maintenances') &&
      response.request().method() === 'GET' &&
      response.status() < 400,
    { timeout: 15000 },
  );
  await searchInput.clear();
  await searchInput.fill(normalizedQuery);
  await searchInput.press('Enter');
  await responsePromise;
}

export async function editAssetMaintenance(
  page: Page,
  identifier: string,
  updates: Record<string, string>,
): Promise<void> {
  await searchAssetMaintenance(page, identifier);

  const row = page.locator('tbody tr').filter({ hasText: identifier }).first();
  await expect(row).toBeVisible();

  await row.getByRole('button', { name: /Actions/i }).click();
  await page.getByRole('menuitem', { name: /Edit/i }).click();

  const dialog = page.getByRole('dialog', { name: /Edit Asset Maintenance/i });
  await expect(dialog).toBeVisible();

  if (updates.notes) {
    await dialog.locator('textarea[name="notes"]').fill(updates.notes);
  }

  if (updates.cost) {
    await dialog.locator('input[name="cost"]').fill(updates.cost);
  }

  const updateResponsePromise = page.waitForResponse(
    response =>
      response.url().includes('/api/asset-maintenances') &&
      ['PUT', 'PATCH'].includes(response.request().method()) &&
      response.status() < 400,
    { timeout: 15000 },
  );

  await dialog.getByRole('button', { name: /Update Maintenance|Save Maintenance|Update|Save/i }).last().click();
  await updateResponsePromise;
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
}

export async function deleteAssetMaintenance(page: Page, notes: string): Promise<void> {
  await searchAssetMaintenance(page, notes);

  const row = page.locator('tbody tr').filter({ hasText: notes }).first();
  await expect(row).toBeVisible();

  const actionsBtn = row.getByRole('button', { name: /Actions/i });
  await actionsBtn.click();

  const deleteItem = page.getByRole('menuitem', { name: /Delete/i });
  await deleteItem.click();

  const confirmBtn = page.getByRole('button', { name: /Delete/i }).last();
  await confirmBtn.click();

  await expect(row).not.toBeVisible();
}
