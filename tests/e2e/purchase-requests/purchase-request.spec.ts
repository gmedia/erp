import { expect, test, type Locator, type Page } from '@playwright/test';

import { login } from '../helpers';
import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import {
    createPurchaseRequest,
    editPurchaseRequest,
    searchPurchaseRequest,
} from './helpers';

async function getFirstAsyncOption(page: Page, url: string) {
    return page.evaluate(async (endpoint) => {
        const apiToken = localStorage.getItem('api_token') || '';
        const response = await fetch(`${endpoint}?per_page=1`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Authorization': `Bearer ${apiToken}`,
            },
        });
        const payload = await response.json();
        const rows = payload.data || payload;
        const [firstRow] = rows;

        return {
            id: String(firstRow.id),
            name: String(firstRow.name),
        };
    }, url);
}

async function selectAsyncOption(page: Page, container: Page | Locator, label: string, optionName: string) {
    await container.getByRole('combobox', { name: label }).click();
    await page
        .getByRole('option', { name: new RegExp(optionName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i') })
        .first()
        .click();
}

const config: ModuleTestConfig = {
    entityName: 'Purchase Request',
    entityNamePlural: 'Purchase Requests',
    route: '/purchase-requests',
    apiPath: '/api/purchase-requests',
    exportApiPath: '/api/purchase-requests/export',
    createEntity: createPurchaseRequest,
    searchEntity: searchPurchaseRequest,
    editEntity: editPurchaseRequest,
    editUpdates: { pr_number: 'PR-E2E-UPDATED-001' },
    expectedExportColumns: [
        'ID',
        'PR Number',
        'Branch',
        'Department',
        'Requested By',
        'Request Date',
        'Required Date',
        'Priority',
        'Status',
        'Estimated Amount',
        'Notes',
        'Created At',
    ],
    sortableColumns: [
        'PR Number',
        'Branch',
        'Department',
        'Requester',
        'Request Date',
        'Required Date',
        'Priority',
        'Status',
        'Estimated Amount',
    ],
    viewType: 'dialog',
    viewDialogTitle: 'Purchase Request Details',
};

test.describe('Purchase Requests Module', () => {
    generateModuleTests(config);

    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/purchase-requests');
        await page
            .waitForResponse((response) => response.url().includes('/api/purchase-requests') && response.status() < 400)
            .catch(() => null);
    });

    test('add dialog starts with empty items and add item button shows icon', async ({ page }) => {
        await page.getByRole('button', { name: /^Add$/i }).first().click();

        const dialog = page.getByRole('dialog', { name: /Add New Purchase Request/i });

        await expect(dialog.getByText('No items added yet.')).toBeVisible();

        const addItemButton = dialog.getByRole('button', { name: /Add Item/i });

        await expect(addItemButton).toBeVisible();
        await expect(addItemButton.locator('svg')).toBeVisible();
    });

    test('add dialog item table shows product and unit names with icon actions', async ({ page }) => {
        const [product, unit] = await Promise.all([
            getFirstAsyncOption(page, '/api/products'),
            getFirstAsyncOption(page, '/api/units'),
        ]);

        await page.getByRole('button', { name: /^Add$/i }).first().click();

        const purchaseRequestDialog = page.getByRole('dialog', { name: /Add New Purchase Request/i });
        await purchaseRequestDialog.getByRole('button', { name: /Add Item/i }).click();

        const itemDialog = page.getByRole('dialog', { name: /^Add Item$/i });

        await selectAsyncOption(page, itemDialog, 'Product', product.name);
        await selectAsyncOption(page, itemDialog, 'Unit', unit.name);
        await itemDialog.getByLabel('Quantity').fill('2');
        await itemDialog.getByRole('button', { name: /Save Item/i }).click();

        const firstRow = purchaseRequestDialog.locator('tbody tr').first();

        await expect(firstRow).toContainText(product.name);
        await expect(firstRow).toContainText(unit.name);
        await expect(firstRow.getByRole('button', { name: /Edit item 1/i })).toBeVisible();
        await expect(firstRow.getByRole('button', { name: /Remove item 1/i })).toBeVisible();
        await expect(firstRow.getByText(/^Edit$/)).toHaveCount(0);
        await expect(firstRow.getByText(/^Remove$/)).toHaveCount(0);
    });

    test('edit dialog keeps item names and icon actions', async ({ page }) => {
        const [product, unit] = await Promise.all([
            getFirstAsyncOption(page, '/api/products'),
            getFirstAsyncOption(page, '/api/units'),
        ]);

        const purchaseRequestNumber = await createPurchaseRequest(page);

        await searchPurchaseRequest(page, purchaseRequestNumber);

        const firstRow = page.locator('tbody tr').first();

        await firstRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', { name: /Edit Purchase Request/i });
        const itemRow = dialog.locator('tbody tr').first();
        const addItemButton = dialog.getByRole('button', { name: /Add Item/i });

        await expect(addItemButton.locator('svg')).toBeVisible();
        await expect(itemRow).toContainText(product.name);
        await expect(itemRow).toContainText(unit.name);
        await expect(itemRow.getByRole('button', { name: /Edit item 1/i })).toBeVisible();
        await expect(itemRow.getByRole('button', { name: /Remove item 1/i })).toBeVisible();
        await expect(itemRow.getByText(/^Edit$/)).toHaveCount(0);
        await expect(itemRow.getByText(/^Remove$/)).toHaveCount(0);
    });
});
