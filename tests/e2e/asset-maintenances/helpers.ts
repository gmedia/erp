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
  const assetOption = page.getByRole('option').first();
  await expect(assetOption).toBeVisible();
  await assetOption.click();

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
  await submitBtn.click();

  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return identifier;
}

export async function searchAssetMaintenance(page: Page, query: string): Promise<void> {
  const searchInput = page.getByPlaceholder(/Search/i).first();
  await expect(searchInput).toBeVisible();
  await searchInput.fill(query);
  await searchInput.press('Enter');
  await page.waitForLoadState('networkidle');
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
