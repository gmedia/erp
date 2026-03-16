import { expect, test, type Locator, type Page } from '@playwright/test';

import { login } from '../helpers';
import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import {
    createPurchaseOrder,
    editPurchaseOrder,
    searchPurchaseOrder,
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
    const escapedOptionName = optionName.replaceAll(/[.*+?^${}()|[\]\\]/g, String.raw`\$&`);
    const optionPattern = new RegExp(escapedOptionName, 'i');

    const option = page
        .locator('[role="option"]:visible, ul[aria-busy]:visible button')
        .filter({ hasText: optionPattern })
        .first();

    await expect(option).toBeVisible();
    await option.click();
}

const config: ModuleTestConfig = {
    entityName: 'Purchase Order',
    entityNamePlural: 'Purchase Orders',
    route: '/purchase-orders',
    apiPath: '/api/purchase-orders',
    exportApiPath: '/api/purchase-orders/export',
    createEntity: createPurchaseOrder,
    searchEntity: searchPurchaseOrder,
    editEntity: editPurchaseOrder,
    editUpdates: { po_number: 'PO-E2E-UPDATED-001' },
    expectedExportColumns: [
        'ID',
        'PO Number',
        'Supplier',
        'Warehouse',
        'Order Date',
        'Expected Delivery Date',
        'Payment Terms',
        'Currency',
        'Status',
        'Subtotal',
        'Tax Amount',
        'Discount Amount',
        'Grand Total',
        'Notes',
        'Created At',
    ],
    sortableColumns: [
        'PO Number',
        'Supplier',
        'Warehouse',
        'Order Date',
        'Expected Delivery',
        'Status',
        'Grand Total',
    ],
    viewType: 'dialog',
    viewDialogTitle: 'Purchase Order Details',
};

test.describe('Purchase Orders Module', () => {
    generateModuleTests(config);

    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/purchase-orders');
        await page
            .waitForResponse((response) => response.url().includes('/api/purchase-orders') && response.status() < 400)
            .catch(() => null);
    });

    test('add dialog starts with empty items and add item opens a dedicated dialog', async ({ page }) => {
        await page.getByRole('button', { name: /^Add$/i }).first().click();

        const dialog = page.getByRole('dialog', { name: /Add New Purchase Order/i });
        const addItemButton = dialog.getByRole('button', { name: /Add Item/i });

        await expect(dialog.getByText('No items added yet.')).toBeVisible();
        await expect(addItemButton.locator('svg')).toBeVisible();

        await addItemButton.click();

        await expect(page.getByRole('dialog', { name: /^Add Item$/i })).toBeVisible();
    });

    test('add dialog item table shows product and unit names after saving item dialog', async ({ page }) => {
        const [product, unit] = await Promise.all([
            getFirstAsyncOption(page, '/api/products'),
            getFirstAsyncOption(page, '/api/units'),
        ]);

        await page.getByRole('button', { name: /^Add$/i }).first().click();

        const purchaseOrderDialog = page.getByRole('dialog', { name: /Add New Purchase Order/i });
        await purchaseOrderDialog.getByRole('button', { name: /Add Item/i }).click();

        const itemDialog = page.getByRole('dialog', { name: /^Add Item$/i });

        await selectAsyncOption(page, itemDialog, 'Product', product.name);
        await selectAsyncOption(page, itemDialog, 'Unit', unit.name);
        await itemDialog.getByLabel('Quantity').fill('2');
        await itemDialog.getByLabel('Unit Price').fill('1000');
        await itemDialog.getByRole('button', { name: /Save Item/i }).click();

        const firstRow = purchaseOrderDialog.locator('tbody tr').first();

        await expect(firstRow).toContainText(product.name);
        await expect(firstRow).toContainText(unit.name);
        await expect(firstRow.getByRole('button', { name: /Edit item 1/i })).toBeVisible();
        await expect(firstRow.getByRole('button', { name: /Remove item 1/i })).toBeVisible();
        await expect(firstRow.getByText(/^Remove$/)).toHaveCount(0);
    });

    test('edit dialog opens dedicated edit item dialog and keeps item labels', async ({ page }) => {
        const [product, unit] = await Promise.all([
            getFirstAsyncOption(page, '/api/products'),
            getFirstAsyncOption(page, '/api/units'),
        ]);

        const purchaseOrderNumber = await createPurchaseOrder(page);

        await searchPurchaseOrder(page, purchaseOrderNumber);

        const firstRow = page.locator('tbody tr').first();

        await firstRow.getByRole('button').last().click();
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', { name: /Edit Purchase Order/i });
        const itemRow = dialog.locator('tbody tr').first();

        await expect(itemRow).toContainText(product.name);
        await expect(itemRow).toContainText(unit.name);

        await itemRow.getByRole('button', { name: /Edit item 1/i }).click();

        const itemDialog = page.getByRole('dialog', { name: /^Edit Item$/i });

        await expect(itemDialog).toBeVisible();
        await expect(itemDialog.getByRole('combobox', { name: 'Product' })).toContainText(product.name);
        await expect(itemDialog.getByRole('combobox', { name: 'Unit' })).toContainText(unit.name);
    });
});
