import { expect, test } from '@playwright/test';
import { login } from '../helpers';

test.describe('Approval Monitoring Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        // Authenticate using login helper
        await login(page, 'admin@dokfin.id', undefined, { requireDashboard: false });
    });

    test('can view approval monitoring dashboard and verify key elements', async ({ page }) => {
        await page.goto('/approval-monitoring');
        
        // Verify page title
        await expect(page).toHaveTitle(/Approval Monitoring/);
        
        // Check for main heading
        await expect(page.getByRole('heading', { name: 'Approval Monitoring' })).toBeVisible();

        // Check for Summary Cards
        await expect(page.locator('text=Pending Approvals')).toBeVisible();
        await expect(page.locator('text=Approved Today')).toBeVisible();
        await expect(page.locator('text=Rejected Today')).toBeVisible();
        await expect(page.locator('text=Avg Processing Time')).toBeVisible();
        
        // Check for Overdue Approvals list section
        await expect(page.locator('text=Overdue Approvals').first()).toBeVisible();

        // Since it's E2E, the page might show either "All caught up" or the data table
        const emptyStateText = page.locator('text=All caught up!');
        const tableHeader = page.getByRole('columnheader', { name: 'Document Type' });

        if (await emptyStateText.isVisible()) {
            await expect(emptyStateText).toBeVisible();
        } else if (await tableHeader.isVisible()) {
            await expect(tableHeader).toBeVisible();
            await expect(page.getByRole('columnheader', { name: 'Submitter' })).toBeVisible();
        }

        // Check if the combobox filter exists
        await expect(page.getByRole('combobox').first()).toBeVisible();
    });

    test('can filter by status', async ({ page }) => {
        await page.goto('/approval-monitoring');
        await page.waitForResponse(r => r.url().includes('/api/approval-monitoring/data') && r.status() < 400);

        // Find and click the status combobox
        const combobox = page.getByRole('combobox').first();
        await expect(combobox).toBeVisible();
        await combobox.click();

        // Verify filter options are visible
        const options = ['All Statuses', 'Pending', 'In Progress', 'Approved', 'Rejected'];
        for (const option of options) {
            await expect(page.getByRole('option', { name: option })).toBeVisible();
        }

        // Select "Pending" and verify selection
        await page.getByRole('option', { name: 'Pending' }).click();
        await expect(combobox).toHaveText(/Pending/);
    });

    test('summary cards display values', async ({ page }) => {
        await page.goto('/approval-monitoring');
        await page.waitForResponse(r => r.url().includes('/api/approval-monitoring/data') && r.status() < 400);

        // Verify "Pending Approvals" card has a numeric value
        const pendingCard = page.locator('[data-slot="card"]').filter({ hasText: 'Pending Approvals' });
        await expect(pendingCard).toBeVisible();
        await expect(pendingCard).toHaveText(/\d+/);

        // Verify "Approved Today" card has a numeric value
        const approvedCard = page.locator('[data-slot="card"]').filter({ hasText: 'Approved Today' });
        await expect(approvedCard).toBeVisible();
        await expect(approvedCard).toHaveText(/\d+/);

        // Verify "Rejected Today" card has a numeric value
        const rejectedCard = page.locator('[data-slot="card"]').filter({ hasText: 'Rejected Today' });
        await expect(rejectedCard).toBeVisible();
        await expect(rejectedCard).toHaveText(/\d+/);

        // Verify "Avg Processing Time" card contains hours indicator
        const avgTimeCard = page.locator('[data-slot="card"]').filter({ hasText: 'Avg Processing Time' });
        await expect(avgTimeCard).toBeVisible();
        await expect(avgTimeCard).toHaveText(/h/);
    });

    test('overdue approvals section renders', async ({ page }) => {
        await page.goto('/approval-monitoring');
        await page.waitForResponse(r => r.url().includes('/api/approval-monitoring/data') && r.status() < 400);

        // Verify "Overdue Approvals" section is visible
        await expect(page.locator('text=Overdue Approvals').first()).toBeVisible();

        // Either table headers are visible OR empty state is shown
        const emptyState = page.locator('text=All caught up!');
        const docTypeHeader = page.getByRole('columnheader', { name: 'Document Type' });

        const isEmpty = await emptyState.isVisible();
        if (isEmpty) {
            await expect(emptyState).toBeVisible();
            await expect(page.locator('text=No approvals are currently overdue.')).toBeVisible();
        } else {
            await expect(docTypeHeader).toBeVisible();
            await expect(page.getByRole('columnheader', { name: 'Submitter' })).toBeVisible();
        }
    });

    test('branch filter scopes the dashboard request', async ({ page }) => {
        await page.goto('/approval-monitoring');
        await page.waitForResponse(r => r.url().includes('/api/approval-monitoring/data') && r.status() < 400);

        const branchSelect = page.getByRole('combobox').filter({ hasText: 'All Branches' });
        await expect(branchSelect).toBeVisible();
        await branchSelect.click();

        await expect(page.getByPlaceholder('Search...')).toBeVisible();
        const firstBranch = page.locator('ul[aria-busy="false"] button').first();
        await expect(firstBranch).toBeVisible({ timeout: 10000 });

        const scopedRequest = page.waitForResponse(
            r =>
                r.url().includes('/api/approval-monitoring/data') &&
                r.url().includes('branch_id=') &&
                r.status() < 400,
            { timeout: 15000 },
        );
        await firstBranch.click();
        await scopedRequest;
    });
});
