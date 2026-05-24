import { test, expect } from '@playwright/test';
import { login } from '../helpers';
import { createBankReconciliation, getReconcilableRow } from './helpers';

test.describe('Bank Reconciliation E2E Tests', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await page.goto('/bank-reconciliations');
        await page.waitForResponse(
            (r) => r.url().includes('/api/bank-reconciliations') && r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can view Bank Reconciliations list', async ({ page }) => {
        await expect(page.locator('table')).toBeVisible();
        await expect(page.locator('tbody tr').first()).toBeVisible();
    });

    test('can search Bank Reconciliations', async ({ page }) => {
        const searchInput = page.getByPlaceholder(/Search/i);
        await expect(searchInput).toBeVisible();
        await searchInput.fill('Cash');
        await searchInput.press('Enter');
        await page.waitForResponse(
            (r) => r.url().includes('/api/bank-reconciliations') && r.status() < 400,
            { timeout: 15000 },
        );
        await expect(page.locator('tbody tr').first()).toBeVisible();
    });

    test('can open actions menu for Bank Reconciliation', async ({ page }) => {
        const row = getReconcilableRow(page);
        await expect(row).toBeVisible();
        await row.getByRole('button').last().click();

        const viewItem = page.getByRole('menuitem', { name: /View/i });
        await expect(viewItem).toBeVisible({ timeout: 5000 });
    });

    test('can export Bank Reconciliations', async ({ page }) => {
        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('button', { name: /export/i }).click();
        const download = await downloadPromise;
        expect(download.suggestedFilename()).toBeTruthy();
    });

    test('Bank Reconciliations datatable has correct checkbox behavior', async ({ page }) => {
        const headerCheckboxes = page.locator('thead [data-testid="select-all"]');
        await expect(headerCheckboxes).toHaveCount(1);
        await expect(headerCheckboxes.first()).toBeVisible();

        const row = getReconcilableRow(page);
        await expect(row).toBeVisible();
        const bodyCheckbox = row.locator('[data-testid="select-row"]').first();
        await expect(bodyCheckbox).toBeVisible();
    });

    test('can sort Bank Reconciliations by all columns', async ({ page }) => {
        test.setTimeout(120000);
        const sortableColumns = [
            'Account',
            'Period Start',
            'Period End',
            'Statement Balance',
            'Book Balance',
            'Difference',
            'Status',
        ];

        for (const column of sortableColumns) {
            const sortButton = page.locator('thead').getByRole('button', { name: column, exact: true });
            await expect(sortButton).toBeVisible();
            await sortButton.click();
            await page.waitForTimeout(500);
            await sortButton.click();
            await page.waitForTimeout(500);
        }
    });

    test('can filter Bank Reconciliations', async ({ page }) => {
        const filterButton = page.getByRole('button', { name: /filter/i });
        await filterButton.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
    });
});

test.describe('Bank Reconciliation Workflow', () => {
    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await page.goto('/bank-reconciliations');
        await page.waitForResponse(
            (r) => r.url().includes('/api/bank-reconciliations') && r.status() < 400,
            { timeout: 15000 },
        );
    });

    test('can open View modal and see Import Statement button', async ({ page }) => {
        test.setTimeout(60000);

        // Ensure we have at least one row
        const row = getReconcilableRow(page);
        const hasRow = await row.isVisible().catch(() => false);
        if (!hasRow) {
            await createBankReconciliation(page);
            await page.waitForResponse(
                (r) => r.url().includes('/api/bank-reconciliations') && r.status() < 400,
                { timeout: 15000 },
            );
        }

        // Open actions menu → View
        const targetRow = getReconcilableRow(page);
        await expect(targetRow).toBeVisible();
        await targetRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: /View/i }).click();

        // Assert View modal opens
        const viewDialog = page.getByRole('dialog').first();
        await expect(viewDialog).toBeVisible({ timeout: 10000 });

        // Assert "Import Statement" button visible (for non-completed status)
        const importBtn = viewDialog.getByRole('button', { name: /Import Statement/i });
        await expect(importBtn).toBeVisible({ timeout: 5000 });
    });

    test('can open Import Statement dialog from View modal', async ({ page }) => {
        test.setTimeout(60000);

        const row = getReconcilableRow(page);
        const hasRow = await row.isVisible().catch(() => false);
        if (!hasRow) {
            await createBankReconciliation(page);
            await page.waitForResponse(
                (r) => r.url().includes('/api/bank-reconciliations') && r.status() < 400,
                { timeout: 15000 },
            );
        }

        // Open View modal
        const targetRow = getReconcilableRow(page);
        await expect(targetRow).toBeVisible();
        await targetRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: /View/i }).click();

        const viewDialog = page.getByRole('dialog').first();
        await expect(viewDialog).toBeVisible({ timeout: 10000 });

        // Click Import Statement
        const importBtn = viewDialog.getByRole('button', { name: /Import Statement/i });
        await expect(importBtn).toBeVisible({ timeout: 5000 });
        await importBtn.click();

        // Assert Import dialog opens with file upload step
        const importDialog = page.getByRole('dialog').last();
        await expect(importDialog).toBeVisible({ timeout: 10000 });

        // Assert file upload label visible
        const fileLabel = importDialog.getByText('Bank Statement File', {
            exact: true,
        });
        await expect(fileLabel).toBeVisible();

        // Assert Next button visible
        const nextBtn = importDialog.getByRole('button', { name: /Next/i });
        await expect(nextBtn).toBeVisible();
    });

    test('can open Reconciliation Workspace from View modal', async ({ page }) => {
        test.setTimeout(60000);

        const row = getReconcilableRow(page);
        const hasRow = await row.isVisible().catch(() => false);
        if (!hasRow) {
            await createBankReconciliation(page);
            await page.waitForResponse(
                (r) => r.url().includes('/api/bank-reconciliations') && r.status() < 400,
                { timeout: 15000 },
            );
        }

        // Open View modal
        const targetRow = getReconcilableRow(page);
        await expect(targetRow).toBeVisible();
        await targetRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: /View/i }).click();

        const viewDialog = page.getByRole('dialog').first();
        await expect(viewDialog).toBeVisible({ timeout: 10000 });

        // Click Reconcile button
        const reconcileBtn = viewDialog.getByRole('button', { name: /Reconcile/i });
        await expect(reconcileBtn).toBeVisible({ timeout: 5000 });
        await reconcileBtn.click();

        // Assert Workspace dialog opens
        const workspace = page.getByRole('dialog').last();
        await expect(workspace).toBeVisible({ timeout: 10000 });

        // Assert title
        await expect(workspace.getByText(/Reconciliation Workspace/i)).toBeVisible();

        // Assert Auto Match button
        await expect(workspace.getByRole('button', { name: /Auto Match/i })).toBeVisible();

        // Assert summary stats visible
        const summaryTexts = ['Total', 'Matched', 'Unmatched', 'Difference'];
        for (const text of summaryTexts) {
            await expect(workspace.getByText(text, { exact: false }).first()).toBeVisible();
        }
    });

    test('Reconciliation Workspace shows bank items table', async ({ page }) => {
        test.setTimeout(60000);

        const row = getReconcilableRow(page);
        const hasRow = await row.isVisible().catch(() => false);
        if (!hasRow) {
            await createBankReconciliation(page);
            await page.waitForResponse(
                (r) => r.url().includes('/api/bank-reconciliations') && r.status() < 400,
                { timeout: 15000 },
            );
        }

        // Open View → Reconcile
        const targetRow = getReconcilableRow(page);
        await expect(targetRow).toBeVisible();
        await targetRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: /View/i }).click();

        const viewDialog = page.getByRole('dialog').first();
        await expect(viewDialog).toBeVisible({ timeout: 10000 });

        const reconcileBtn = viewDialog.getByRole('button', { name: /Reconcile/i });
        await expect(reconcileBtn).toBeVisible({ timeout: 5000 });
        await reconcileBtn.click();

        const workspace = page.getByRole('dialog').last();
        await expect(workspace).toBeVisible({ timeout: 10000 });

        // Assert table headers present
        const expectedHeaders = ['Date', 'Description', 'Debit', 'Credit', 'Status'];
        for (const header of expectedHeaders) {
            await expect(
                workspace.getByRole('columnheader', { name: new RegExp(header, 'i') }).first(),
            ).toBeVisible({ timeout: 5000 });
        }
    });

    test('can trigger Auto Match from workspace', async ({ page }) => {
        test.setTimeout(60000);

        const row = getReconcilableRow(page);
        const hasRow = await row.isVisible().catch(() => false);
        if (!hasRow) {
            await createBankReconciliation(page);
            await page.waitForResponse(
                (r) => r.url().includes('/api/bank-reconciliations') && r.status() < 400,
                { timeout: 15000 },
            );
        }

        // Open View → Reconcile
        const targetRow = getReconcilableRow(page);
        await expect(targetRow).toBeVisible();
        await targetRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: /View/i }).click();

        const viewDialog = page.getByRole('dialog').first();
        await expect(viewDialog).toBeVisible({ timeout: 10000 });

        const reconcileBtn = viewDialog.getByRole('button', { name: /Reconcile/i });
        await expect(reconcileBtn).toBeVisible({ timeout: 5000 });
        await reconcileBtn.click();

        const workspace = page.getByRole('dialog').last();
        await expect(workspace).toBeVisible({ timeout: 10000 });

        // Click Auto Match
        const autoMatchBtn = workspace.getByRole('button', { name: /Auto Match/i });
        await expect(autoMatchBtn).toBeVisible();

        const autoMatchResponse = page.waitForResponse(
            (r) => r.url().includes('/auto-match') && r.status() < 400,
            { timeout: 15000 },
        );

        await autoMatchBtn.click();
        await autoMatchResponse;

        // Assert toast notification appears
        const toast = page.locator('[data-sonner-toast]').first();
        await expect(toast).toBeVisible({ timeout: 10000 });
    });

    test('Import Statement button not visible for completed reconciliation', async ({ page }) => {
        test.setTimeout(60000);

        // Look for a completed status row
        const completedRow = page
            .locator('tbody tr')
            .filter({ hasText: /completed/i })
            .first();

        const hasCompleted = await completedRow.isVisible().catch(() => false);
        if (!hasCompleted) {
            test.skip();
            return;
        }

        // Open View modal for completed row
        await completedRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: /View/i }).click();

        const viewDialog = page.getByRole('dialog').first();
        await expect(viewDialog).toBeVisible({ timeout: 10000 });

        // Assert Import Statement button is NOT visible
        const importBtn = viewDialog.getByRole('button', { name: /Import Statement/i });
        await expect(importBtn).not.toBeVisible({ timeout: 3000 });
    });
});
