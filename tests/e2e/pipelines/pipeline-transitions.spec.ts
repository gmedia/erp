import { test, expect } from '@playwright/test';
import { createPipeline, searchPipeline } from './helpers';

test.describe('Pipeline Transition Configuration', () => {
  let initialPipelineCode: string;

  test.beforeEach(async ({ page }) => {
    await page.goto('/pipelines');
    initialPipelineCode = await createPipeline(page);
    await searchPipeline(page, initialPipelineCode);
  });

  test('can add, edit, and delete pipeline transitions and actions', async ({ page }) => {
    // 1. Open Pipeline Edit Modal
    const row = page.locator('tr').filter({ hasText: initialPipelineCode }).first();
    await row.getByRole('button', { name: /Actions/i }).click();
    await page.getByRole('menuitem', { name: /Edit/i }).click();

    await expect(page.getByRole('dialog')).toBeVisible();

    // 2. Add two states first (required for transitions)
    await page.getByRole('tab', { name: 'States' }).click();
    
    // Add Initial State
    await page.getByRole('button', { name: 'Add State' }).click();
    let stateRow = page.locator('tr').filter({ has: page.getByPlaceholder('Code') });
    await stateRow.getByPlaceholder('Code').fill('draft');
    await stateRow.getByPlaceholder('Name').fill('Draft');
    await stateRow.getByRole('combobox').click();
    await page.getByRole('option', { name: 'Initial' }).click();
    await stateRow.locator('input[type="number"]').fill('10');
    await stateRow.getByTitle('Save state').click();
    await expect(page.getByText('Pipeline state created successfully.')).toBeVisible();
    await page.waitForTimeout(500);

    // Add Final State
    await page.getByRole('button', { name: 'Add State' }).click();
    stateRow = page.locator('tr').filter({ has: page.getByPlaceholder('Code') });
    await stateRow.getByPlaceholder('Code').fill('approved');
    await stateRow.getByPlaceholder('Name').fill('Approved');
    await stateRow.getByRole('combobox').click();
    await page.getByRole('option', { name: 'Final' }).click();
    await stateRow.locator('input[type="number"]').fill('20');
    await stateRow.getByTitle('Save state').click();
    await expect(page.getByText('Pipeline state created successfully.')).toBeVisible();
    await page.waitForTimeout(500);

    // 3. Go to Transitions Tab
    await page.getByRole('tab', { name: 'Transitions' }).click();
    await expect(page.getByText('Pipeline Transitions', { exact: true })).toBeVisible();
    await expect(page.getByText('No transitions defined. Add one to enable state movement.')).toBeVisible();

    // 4. Add a new Transition
    await page.getByRole('button', { name: 'Add Transition' }).click();
    await expect(page.getByRole('heading', { name: 'New Transition' })).toBeVisible();

    // Fill transition details
    await page.getByLabel('Name (e.g. Approve)').fill('Approve Pipeline');
    await page.getByLabel('Code (e.g. approve_order)').fill('approve_transition');
    
    // Note: since Draft is the first state, From and To are probably already "Draft". 
    // Let's set To State to Approved.
    // The SelectField uses labels "From State" and "To State".
    await page.getByRole('combobox', { name: 'To State' }).click();
    await page.getByRole('option', { name: 'Approved' }).click();

    await page.getByLabel('Sort Order').fill('10');

    // Add an Action
    await page.getByRole('tab', { name: /Actions \(/ }).click();
    await page.getByRole('button', { name: 'Add Action' }).click();
    
    // The action should be "Update Field" by default. Let's just enter execution order.
    // Use the name attribute or placeholder if locator is tricky, but label works for the second group.
    // "Order"
    await page.getByRole('spinbutton', { name: 'Order' }).fill('10');
    await page.getByLabel('Configuration (JSON)').fill('{"status": "approved"}');

    // Save Transition
    await page.getByRole('button', { name: 'Save Transition' }).click();
    
    // Verify Toast and Table
    await expect(page.getByText('Pipeline transition created successfully.')).toBeVisible();
    await page.waitForTimeout(500);
    
    const transRow = page.locator('tr').filter({ hasText: 'Approve Pipeline' });
    await expect(transRow).toBeVisible();
    await expect(transRow.locator('td', { hasText: 'Draft' })).toBeVisible();
    await expect(transRow.locator('td', { hasText: 'Approved' })).toBeVisible();
    await expect(transRow.locator('td', { hasText: '1 Actions' })).toBeVisible();

    // 5. Edit Transition
    await transRow.getByTitle('Edit transition').click();
    await expect(page.getByRole('heading', { name: 'Edit Transition' })).toBeVisible();
    
    await page.getByLabel('Name (e.g. Approve)').fill('Approve Pipeline Updated');
    await page.getByRole('tab', { name: /Actions \(/ }).click();
    await page.getByRole('button', { name: 'Add Action' }).click(); // Add a second action
    
    await page.getByRole('button', { name: 'Save Transition' }).click();
    await expect(page.getByText('Pipeline transition updated successfully.')).toBeVisible();
    await page.waitForTimeout(500);
    
    // Verify Updates Table
    await expect(page.locator('tr').filter({ hasText: 'Approve Pipeline Updated' })).toBeVisible();
    await expect(page.locator('tr').filter({ hasText: 'Approve Pipeline Updated' }).locator('td', { hasText: '2 Actions' })).toBeVisible();

    // 6. Delete Transition
    page.once('dialog', dialog => dialog.accept());
    await page.locator('tr').filter({ hasText: 'Approve Pipeline Updated' }).getByTitle('Delete transition').click();
    
    await expect(page.getByText('Pipeline transition deleted successfully.')).toBeVisible();
    await expect(page.getByText('No transitions defined. Add one to enable state movement.')).toBeVisible();
  });
});
