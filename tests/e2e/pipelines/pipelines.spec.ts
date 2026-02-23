import { test, expect } from '@playwright/test';
import { createPipeline, searchPipeline, editPipeline } from './helpers';
import { login } from '../helpers';

test.describe('Pipeline Management', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('can navigate to pipelines and view list', async ({ page }) => {
    await page.goto('/pipelines');
    
    await expect(page).toHaveURL(/.*\/pipelines/);
    await expect(page.getByText('Pipelines', { exact: true }).first()).toBeVisible();
    await expect(page.getByRole('button', { name: 'Add', exact: true })).toBeVisible();
  });

  test('can create a new pipeline', async ({ page }) => {
    const code = await createPipeline(page);
    
    // Verify it appears in the list
    await searchPipeline(page, code);
    const row = page.locator('tr').filter({ hasText: code });
    await expect(row).toBeVisible();
  });

  test('can view pipeline details in view modal', async ({ page }) => {
    const code = await createPipeline(page);
    
    await searchPipeline(page, code);
    
    const row = page.locator('tr').filter({ hasText: code }).first();
    await expect(row).toBeVisible();
    
    const viewItem = row.getByRole('button', { name: /Actions/i });
    await viewItem.click();
    await page.getByRole('menuitem', { name: /View/i }).click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();
    await expect(dialog.getByText(code)).toBeVisible();
    await expect(dialog.getByText('Asset')).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(dialog).not.toBeVisible();
  });

  test('can update a pipeline', async ({ page }) => {
    const code = await createPipeline(page);
    
    await editPipeline(page, code, {
      name: 'Updated Pipeline Name',
      is_active: false
    });
    
    await searchPipeline(page, code);
    
    const row = page.locator('tr').filter({ hasText: code }).first();
    await expect(row.getByText('Updated Pipeline Name')).toBeVisible();
    // Inactive status would be visible in the row
    await expect(row.getByText('Inactive')).toBeVisible();
  });

  test('can delete a pipeline', async ({ page }) => {
    const code = await createPipeline(page);
    
    await searchPipeline(page, code);
    const row = page.locator('tr').filter({ hasText: code }).first();
    
    const actionsBtn = row.getByRole('button', { name: /Actions/i });
    await actionsBtn.click();
    await page.getByRole('menuitem', { name: /Delete/i }).click();
    
    // confirm delete
    const dialog = page.getByRole('alertdialog');
    await expect(dialog).toBeVisible();
    await dialog.getByRole('button', { name: /Delete/i }).click();
    
    await expect(dialog).not.toBeVisible();
    
    await searchPipeline(page, code);
    await expect(page.locator('tr').filter({ hasText: code })).toHaveCount(0);
  });
});
