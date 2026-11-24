import { expect, test, type Locator, type Page } from '@playwright/test';

import { DEFAULT_E2E_EMAIL, login, searchAndWaitForApi } from '../helpers';

const FORBIDDEN_MANUAL_STATUSES = ['Confirmed', 'Rejected'];

async function firstOptionName(page: Page, url: string): Promise<string> {
    return page.evaluate(async (endpoint) => {
        const apiToken = localStorage.getItem('api_token') || '';
        const response = await fetch(`${endpoint}?per_page=1`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Authorization: `Bearer ${apiToken}`,
            },
        });
        const payload = await response.json();
        const [firstRow] = payload.data || payload;
        return String(firstRow.name);
    }, url);
}

async function selectAsyncOption(
    page: Page,
    container: Locator,
    label: string,
    optionName: string,
): Promise<void> {
    await container.getByRole('combobox', { name: label }).click();
    const escaped = optionName.replaceAll(
        /[.*+?^${}()|[\]\\]/g,
        String.raw`\$&`,
    );
    const option = page
        .locator('[role="option"]:visible, ul[aria-busy]:visible button')
        .filter({ hasText: new RegExp(escaped, 'i') })
        .first();
    await expect(option).toBeVisible();
    await option.click();
}

async function openCreateForm(page: Page): Promise<void> {
    const listResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/api/purchase-orders') &&
            response.status() < 400,
    );
    await page.goto('/purchase-orders');
    await listResponse;
    await page.getByRole('button', { name: /^Add$/i }).first().click();
    await expect(
        page.getByRole('dialog', { name: /Add New Purchase Order/i }),
    ).toBeVisible();
}

test.describe('Purchase Order approval flow (UI)', () => {
    test('form tidak menawarkan status confirmed/rejected untuk dokumen baru', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await openCreateForm(page);

        const dialog = page.getByRole('dialog', {
            name: /Add New Purchase Order/i,
        });
        await dialog.getByRole('combobox', { name: 'Status' }).click();

        for (const label of FORBIDDEN_MANUAL_STATUSES) {
            await expect(
                page.getByRole('option', { name: label, exact: true }),
            ).toHaveCount(0);
        }
        await expect(
            page.getByRole('option', { name: 'Draft', exact: true }),
        ).toBeVisible();
    });

    test('dokumen dibuat via UI, Submit, lalu Approve di My Approvals', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        await openCreateForm(page);

        const dialog = page.getByRole('dialog', {
            name: /Add New Purchase Order/i,
        });
        const [supplier, warehouse] = await Promise.all([
            firstOptionName(page, '/api/suppliers'),
            firstOptionName(page, '/api/warehouses'),
        ]);
        await selectAsyncOption(page, dialog, 'Supplier', supplier);
        await selectAsyncOption(page, dialog, 'Warehouse', warehouse);

        await dialog.getByRole('button', { name: /Add Item/i }).click();
        const itemDialog = page.getByRole('dialog', {
            name: /^Add Item$/i,
        });
        await selectAsyncOption(
            page,
            itemDialog,
            'Product',
            'Executive Office Desk',
        );
        await selectAsyncOption(page, itemDialog, 'Unit', 'Buah');
        await itemDialog.getByLabel('Quantity').fill('2');
        await itemDialog.getByRole('button', { name: /Save Item/i }).click();

        const createResponse = page.waitForResponse(
            (response) =>
                response.url().includes('/api/purchase-orders') &&
                response.request().method() === 'POST' &&
                response.status() < 400,
        );
        await dialog.getByRole('button', { name: 'Add', exact: true }).click();
        const createPayload = await (await createResponse).json();
        const poNumber = String(createPayload?.data?.po_number ?? '');
        expect(poNumber, 'PO number dari respons POST').not.toBe('');

        await searchAndWaitForApi(
            page,
            page.getByPlaceholder(/search/i),
            poNumber as string,
            '/api/purchase-orders',
        );

        await page
            .locator('tbody tr')
            .first()
            .getByRole('button')
            .last()
            .click();
        await page.getByRole('menuitem', { name: /Details|View/i }).click();

        const submitTransition = page.waitForResponse(
            (response) =>
                response.url().includes('/api/entity-states/') &&
                response.status() < 400,
        );
        await page.getByRole('button', { name: 'Submit', exact: true }).click();
        await submitTransition;

        await expect(page.getByText(/Pending Approval/i).first()).toBeVisible();

        await page.getByRole('tab', { name: 'Approvals', exact: true }).click();
        await expect(page.getByText(/^Step 1\b/).first()).toBeVisible();

        await page.context().clearCookies();
        await login(page, DEFAULT_E2E_EMAIL, undefined, {
            requireDashboard: false,
        });
        await page.goto('/my-approvals');
        await expect(page.getByRole('tab', { name: /Pending/i })).toBeVisible();

        const approveResponse = page.waitForResponse(
            (response) =>
                response.url().includes('/api/my-approvals') &&
                response.status() < 400,
        );
        await page
            .getByRole('button', { name: /^Approve$/ })
            .first()
            .click();
        await page
            .getByRole('button', { name: 'Confirm Approval', exact: true })
            .click();
        await approveResponse;

        await page.goto('/purchase-orders');
        await searchAndWaitForApi(
            page,
            page.getByPlaceholder(/search/i),
            poNumber as string,
            '/api/purchase-orders',
        );
        await expect(
            page
                .locator('tbody tr')
                .first()
                .getByText(/Confirmed/i),
        ).toBeVisible();
    });
});
