import { test, expect } from '@playwright/test';
import { createPipeline, searchPipeline } from './helpers';

test.describe('Pipeline State Configuration', () => {
  let initialPipelineCode: string;

  test.beforeEach(async ({ page }) => {
    // Navigate and create a baseline pipeline we can work with
    await page.goto('/pipelines');
    initialPipelineCode = await createPipeline(page);
    await searchPipeline(page, initialPipelineCode);
  });

  test('can add, edit, and delete pipeline states', async ({ page }) => {
    // 1. Open Pipeline Edit Modal
    const row = page.locator('tr').filter({ hasText: initialPipelineCode }).first();
    await row.getByRole('button', { name: /Actions/i }).click();
    await page.getByRole('menuitem', { name: /Edit/i }).click();

    await expect(page.getByRole('dialog')).toBeVisible();

    // Navigate to States tab
    await page.getByRole('tab', { name: 'States' }).click();

    // The Pipeline States table should be visible
    await expect(page.getByText('Pipeline States', { exact: true })).toBeVisible();
    await expect(page.getByText('No states added yet')).toBeVisible();

    // 2. Add a new Pipeline State
    await page.getByRole('button', { name: 'Add State' }).click();
    
    // Fill state form fields
    const newRow = page.locator('tr').filter({ has: page.getByPlaceholder('Code') });
    await newRow.getByPlaceholder('Code').fill('state_one');
    await newRow.getByPlaceholder('Name').fill('State One');
    
    // Change Type Select
    await newRow.getByRole('combobox').click();
    await page.getByRole('option', { name: 'Initial' }).click();
    
    await newRow.locator('input[type="number"]').fill('10');
    await newRow.getByPlaceholder('#HEX').fill('#00ff00');
    
    // Save state
    await newRow.getByTitle('Save state').click();
    
    // Verify toast
    await expect(page.getByText('Pipeline state created successfully.')).toBeVisible();
    
    // Wait for the states to reload
    await page.waitForTimeout(500);

    // Verify row added
    await expect(page.locator('td', { hasText: 'state_one' })).toBeVisible();
    await expect(page.locator('td', { hasText: 'State One' })).toBeVisible();
    await expect(page.locator('td', { hasText: 'initial' })).toBeVisible();

    // 3. Edit the Pipeline State
    const actionRow = page.locator('tr').filter({ hasText: 'state_one' });
    await actionRow.getByTitle('Edit state').click();
    
    // Changing the name
    const editRow = page.locator('tr').filter({ has: page.getByPlaceholder('Name') });
    await editRow.getByPlaceholder('Name').fill('State One Updated');
    await editRow.getByTitle('Save state').click();
    
    // Verify toast
    await expect(page.getByText('Pipeline state updated successfully.')).toBeVisible();
    
    // Verify updated name
    await expect(page.locator('td', { hasText: 'State One Updated' })).toBeVisible();

    // 4. Delete the Pipeline State
    page.once('dialog', dialog => dialog.accept());
    
    const deleteRow = page.locator('tr').filter({ hasText: 'state_one' });
    await deleteRow.getByTitle('Delete state').click();
    
    // Verify toast
    await expect(page.getByText('Pipeline state deleted successfully.')).toBeVisible();
    
    // Verify it's gone
    await expect(page.getByText('No states added yet')).toBeVisible();
  });
});
