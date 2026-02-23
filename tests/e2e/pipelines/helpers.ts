import { Page, expect } from '@playwright/test';
import { createEntity, EntityConfig } from '../helpers';

/**
 * Create a new pipeline via the UI.
 *
 * @param page - Playwright Page object.
 * @param overrides - Optional fields to override the default values.
 * @returns The unique code used for the created pipeline.
 */
export async function createPipeline(
  page: Page,
  overrides: Partial<{
    name: string;
    code: string;
    entity_type: string;
    description: string;
    version: string;
    is_active: boolean;
    conditions: string;
  }> = {}
): Promise<string> {
  const timestamp = Date.now().toString().slice(-6);
  const defaultCode = `pipe_${timestamp}`;

  const config: EntityConfig = {
    route: '/pipelines',
    returnField: 'code',
    fields: [
      { name: 'name', type: 'text', defaultValue: `Test Pipeline ${timestamp}` },
      { name: 'code', type: 'text', defaultValue: defaultCode },
      { name: 'entity_type', type: 'select', selector: 'Entity Type', defaultValue: 'Asset' },
      { name: 'version', type: 'text', defaultValue: '1' },
      { name: 'description', type: 'textarea', defaultValue: 'This is a test pipeline' },
      { name: 'is_active', type: 'select', selector: 'Status', defaultValue: 'Active' },
    ],
  };

  return createEntity(page, config, overrides);
}

/**
 * Search for a pipeline by code.
 *
 * @param page - Playwright Page object.
 * @param code - Code to search for.
 */
export async function searchPipeline(page: Page, code: string): Promise<void> {
  const searchInput = page.getByPlaceholder('Search name, code, or description...');
  await expect(searchInput).toBeVisible();
  await searchInput.clear();
  await searchInput.type(code);
  await page.keyboard.press('Enter');
  
  await page.waitForResponse(r => r.url().includes('/api/pipelines') && r.status() === 200).catch(() => null);
  await page.waitForTimeout(500);
}

/**
 * Edit an existing pipeline via the UI.
 *
 * @param page - Playwright Page object.
 * @param code - Current pipeline code to locate.
 * @param updates - Fields to update.
 */
export async function editPipeline(
  page: Page,
  code: string,
  updates: { name?: string; is_active?: boolean }
): Promise<void> {
  await searchPipeline(page, code);

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
    if (await statusTrigger.count() === 0) {
        // accessible name might differ
        const labelTrigger = dialog.getByLabel('Status');
        await labelTrigger.click();
    } else {
        await statusTrigger.click();
    }
    
    await page.getByRole('option', { name: updates.is_active ? 'Active' : 'Inactive', exact: true }).click();
  }

  const updateBtn = dialog.getByRole('button', { name: /Update/ });
  await expect(updateBtn).toBeVisible();
  await updateBtn.click();
  
  await expect(dialog).not.toBeVisible({ timeout: 10000 });
}
