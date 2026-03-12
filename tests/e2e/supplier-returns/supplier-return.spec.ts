import { expect, test, type Locator, type Page } from '@playwright/test';

import { login } from '../helpers';
import { generateModuleTests, ModuleTestConfig } from '../shared-test-factories';
import {
    createSupplierReturn,
    editSupplierReturn,
    searchSupplierReturn,
} from './helpers';

async function getFirstAsyncOption(page: Page, url: string) {
    return page.evaluate(async (endpoint) => {
        const apiToken = localStorage.getItem('api_token') || '';
        const response = await fetch(`${endpoint}?per_page=1`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Authorization: `Bearer ${apiToken}`,
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
    .getByRole('option', { name: new RegExp(optionName.replaceAll(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i') })
        .first()
        .click();
}

const config: ModuleTestConfig = {
    entityName: 'Supplier Return',
    entityNamePlural: 'Supplier Returns',
    route: '/supplier-returns',
    apiPath: '/api/supplier-returns',
    exportApiPath: '/api/supplier-returns/export',
    createEntity: createSupplierReturn,
    searchEntity: searchSupplierReturn,
    editEntity: editSupplierReturn,
    editUpdates: { return_number: 'SR-E2E-UPDATED-001' },
    expectedExportColumns: [
        'ID',
        'Return Number',
        'PO Number',
        'GR Number',
        'Supplier',
        'Warehouse',
        'Return Date',
        'Reason',
        'Status',
        'Notes',
        'Created At',
    ],
    sortableColumns: [
        'Return Number',
        'PO Number',
        'GR Number',
        'Supplier',
        'Warehouse',
        'Return Date',
        'Reason',
        'Status',
    ],
    viewType: 'dialog',
    viewDialogTitle: 'Supplier Return Details',
};

test.describe('Supplier Returns Module', () => {
    generateModuleTests(config);

    test.beforeEach(async ({ page }) => {
        await login(page);
        await page.goto('/supplier-returns');
        await page
            .waitForResponse((response) => response.url().includes('/api/supplier-returns') && response.status() < 400)
            .catch(() => null);
    });

    test('add dialog starts with empty items and add item opens a dedicated dialog', async ({ page }) => {
        await page.getByRole('button', { name: /^Add$/i }).first().click();

        const dialog = page.getByRole('dialog', { name: /Add New Supplier Return/i });
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

        const supplierReturnDialog = page.getByRole('dialog', { name: /Add New Supplier Return/i });
        await supplierReturnDialog.getByRole('button', { name: /Add Item/i }).click();

        const itemDialog = page.getByRole('dialog', { name: /^Add Item$/i });

        await itemDialog.getByLabel('GR Item ID').fill('1');
        await selectAsyncOption(page, itemDialog, 'Product', product.name);
        await selectAsyncOption(page, itemDialog, 'Unit', unit.name);
        await itemDialog.getByLabel('Quantity Returned').fill('2');
        await itemDialog.getByLabel('Unit Price').fill('1000');
        await itemDialog.getByRole('button', { name: /Save Item/i }).click();

        const firstRow = supplierReturnDialog.locator('tbody tr').first();

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

        const supplierReturnNumber = await createSupplierReturn(page);

        await searchSupplierReturn(page, supplierReturnNumber);

        const firstRow = page.locator('tbody tr').first();

        // Use force to prevent hanging on Radix UI Dropdown triggers flakily
        await firstRow.getByRole('button', { name: /actions/i }).last().click({ force: true });
        await page.getByRole('menuitem', { name: 'Edit' }).click();

        const dialog = page.getByRole('dialog', { name: /Edit Supplier Return/i });
        const itemRow = dialog.locator('tbody tr').first();

        await expect(itemRow).toContainText(product.name);
        await expect(itemRow).toContainText(unit.name);

        await itemRow.getByRole('button', { name: /Edit item 1/i }).click();

        const itemDialog = page.getByRole('dialog', { name: /^Edit Item$/i });

        await expect(itemDialog).toBeVisible();
        await expect(itemDialog.getByRole('combobox', { name: 'Product' })).toContainText(product.name);
        await expect(itemDialog.getByRole('combobox', { name: 'Unit' })).toContainText(unit.name);
        await expect(itemDialog.getByLabel('GR Item ID')).not.toHaveValue('');
    });
});
