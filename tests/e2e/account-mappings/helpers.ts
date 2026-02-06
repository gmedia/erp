import { Page, expect } from '@playwright/test';
import { login } from '../helpers';

async function clickFirstMatchingOption(page: Page, name: RegExp): Promise<void> {
  for (let attempt = 0; attempt < 5; attempt++) {
    const option = page.getByRole('option', { name }).first();
    await option.waitFor({ state: 'visible', timeout: 15000 });
    try {
      await option.click();
      return;
    } catch (error) {
      if (attempt === 4) throw error;
    }
  }
}

export async function createAccountMapping(page: Page): Promise<{
  sourceCode: string;
  targetCode: string;
  notes: string;
}> {
  const timestamp = Date.now();
  const sourceCode = '52000';
  const targetCode = timestamp % 2 === 0 ? '11110' : '11120';

  await login(page);
  await page.goto('/account-mappings');
  await page.waitForLoadState('networkidle');

  const addBtn = page.getByRole('button', { name: /Add/i }).first();
  await expect(addBtn).toBeVisible({ timeout: 15000 });
  await addBtn.click();

  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible({ timeout: 15000 });

  const combos = dialog.locator('[role="combobox"]');
  await expect(combos.first()).toBeVisible({ timeout: 15000 });

  await combos.nth(0).click();
  const search1 = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  await search1.fill('COA 2025 Standard');
  await clickFirstMatchingOption(page, /COA 2025 Standard/i);

  await combos.nth(1).click();
  const search2 = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  await search2.fill(sourceCode);
  await clickFirstMatchingOption(page, new RegExp(sourceCode));

  await combos.nth(2).click();
  const search3 = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  await search3.fill('COA 2026 Enhanced');
  await clickFirstMatchingOption(page, /COA 2026 Enhanced/i);

  await combos.nth(3).click();
  const search4 = page.getByPlaceholder('Search...').filter({ visible: true }).last();
  await search4.fill(targetCode);
  await clickFirstMatchingOption(page, new RegExp(targetCode));

  await combos.nth(4).click();
  await page.getByRole('option', { name: 'Rename', exact: true }).click();

  const notes = `notes-${timestamp}`;
  await dialog.locator('textarea[name="notes"]').fill(notes);

  const submitBtn = dialog.getByRole('button', { name: /Create|Submit/i });
  await expect(submitBtn).toBeVisible();
  await submitBtn.click();

  await expect(dialog).not.toBeVisible({ timeout: 15000 });

  return { sourceCode, targetCode, notes };
}

export async function searchAccountMappings(page: Page, query: string): Promise<void> {
  const input = page.getByPlaceholder('Search account mappings...');
  await input.waitFor({ state: 'visible' });
  await input.fill(query);
  await input.press('Enter');
  await page.waitForLoadState('networkidle');
}

export function findAccountMappingRow(page: Page, sourceCode: string, targetCode: string) {
  return page.locator('tr', { hasText: sourceCode }).filter({ hasText: targetCode }).first();
}
