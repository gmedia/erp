import { expect, Page, test } from '@playwright/test';

import { login, searchAndWaitForApi } from '../helpers';

const API_HEADERS = (token: string) => ({
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    Authorization: `Bearer ${token}`,
});

interface SeedResult {
    customerId: number;
    branchId: number;
    fiscalYearId: number;
    accountId: number;
    sentInvoiceNumber: string;
    draftInvoiceNumber: string;
    paidInvoiceNumber: string;
    receiptId: number;
    receiptNumber: string;
}

async function seedAllocationFixtures(page: Page): Promise<SeedResult> {
    return page.evaluate(async () => {
        const token = localStorage.getItem('api_token') || '';
        const headers = {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            Authorization: `Bearer ${token}`,
        };

        const firstId = async (url: string): Promise<number> => {
            const json = await (await fetch(url, { headers })).json();
            return Number((json.data || json)[0].id);
        };

        const postableAccountId = async (): Promise<number> => {
            const json = await (await fetch('/api/accounts?per_page=100', { headers })).json();
            const row = (json.data || []).find((a: { code: string }) => a.code === '11110');
            if (!row) {
                throw new Error('akun postable 11110 tidak ditemukan');
            }
            return Number(row.id);
        };

        const [customerId, branchId, fiscalYearId, accountId] = await Promise.all([
            firstId('/api/customers?per_page=1'),
            firstId('/api/branches?per_page=1'),
            firstId('/api/fiscal-years?per_page=1'),
            postableAccountId(),
        ]);

        const today = new Date().toISOString().slice(0, 10);
        const dueDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
        const items = [{ account_id: accountId, description: 'Allocation select test', quantity: 1, unit_price: 1000000, tax_percent: 0 }];

        const createInvoice = async (status: 'draft' | 'sent' | 'paid') => {
            const created = await (await fetch('/api/customer-invoices', {
                method: 'POST',
                headers,
                body: JSON.stringify({
                    customer_id: customerId,
                    branch_id: branchId,
                    fiscal_year_id: fiscalYearId,
                    invoice_date: today,
                    due_date: dueDate,
                    currency: 'IDR',
                    status: 'draft',
                    items,
                }),
            })).json();
            const id = created?.data?.id;
            if (status !== 'draft') {
                const updated = await (await fetch(`/api/customer-invoices/${id}`, {
                    method: 'PUT',
                    headers,
                    body: JSON.stringify({ status, items }),
                })).json();
                if (updated?.data?.status !== status) {
                    throw new Error(`invoice ${String(id)} gagal ke status ${status}`);
                }
            }
            return { id, number: String(created?.data?.invoice_number ?? '') };
        };

        const sent = await createInvoice('sent');
        const draft = await createInvoice('draft');
        const paid = await createInvoice('paid');

        const receiptResponse = await fetch('/api/ar-receipts', {
            method: 'POST',
            headers,
            body: JSON.stringify({
                customer_id: customerId,
                branch_id: branchId,
                fiscal_year_id: fiscalYearId,
                receipt_date: today,
                payment_method: 'bank_transfer',
                bank_account_id: accountId,
                currency: 'IDR',
                total_amount: 300000,
                status: 'draft',
                allocations: [{ customer_invoice_id: sent.id, allocated_amount: 300000 }],
            }),
        });
        const receiptPayload = await receiptResponse.json();

        return {
            customerId,
            branchId,
            fiscalYearId,
            accountId,
            sentInvoiceNumber: sent.number,
            draftInvoiceNumber: draft.number,
            paidInvoiceNumber: paid.number,
            receiptId: Number(receiptPayload?.data?.id),
            receiptNumber: String(receiptPayload?.data?.receipt_number ?? ''),
        };
    });
}

async function openReceiptEdit(page: Page, receiptNumber: string): Promise<void> {
    await searchAndWaitForApi(page, page.getByPlaceholder(/search/i), receiptNumber, '/api/ar-receipts');
    const row = page.locator('tbody tr').filter({ hasText: receiptNumber }).first();
    await row.getByRole('button').last().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
}

test.describe('AR Receipt allocation selects', () => {
    test.describe.configure({ mode: 'serial' });

    let fixtures: SeedResult;

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        fixtures ??= await seedAllocationFixtures(page);
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/ar-receipts') && r.status() < 400,
            { timeout: 30000 },
        );
        await page.goto('/ar-receipts');
        await listed;
    });

    test('edit dokumen lama menampilkan label faktur', async ({ page }) => {
        expect(fixtures.receiptNumber).not.toBe('');

        await openReceiptEdit(page, fixtures.receiptNumber);
        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog).toBeVisible();
        await formDialog.getByRole('button', { name: /Edit item 1/i }).click();

        const invoiceSelect = page.getByRole('combobox', { name: /customer invoice/i }).first();
        await expect(invoiceSelect).toContainText(fixtures.sentInvoiceNumber);
        await expect(invoiceSelect).not.toContainText('Select Customer Invoice');
    });

    test('faktur draft dan lunas tidak ditawarkan di pilihan alokasi', async ({ page }) => {
        await openReceiptEdit(page, fixtures.receiptNumber);
        await page.getByRole('button', { name: 'Add Item' }).click();
        const allocationDialog = page.getByRole('dialog').last();

        const listed = page.waitForResponse((r) => r.url().includes('/api/customer-invoices') && r.status() < 400);
        await allocationDialog.getByRole('combobox', { name: /customer invoice/i }).click();
        await listed;

        for (const hidden of [fixtures.draftInvoiceNumber, fixtures.paidInvoiceNumber]) {
            await expect(page.getByRole('button', { name: new RegExp(hidden) })).toHaveCount(0);
        }
        await expect(page.getByRole('button', { name: new RegExp(fixtures.sentInvoiceNumber) })).toHaveCount(1);
    });

    test('ganti pelanggan di header mengosongkan baris alokasi', async ({ page }) => {
        await openReceiptEdit(page, fixtures.receiptNumber);
        await expect(page.getByRole('row').filter({ hasText: fixtures.sentInvoiceNumber })).toHaveCount(1);

        const customerSelect = page.getByRole('combobox', { name: 'Customer' }).first();
        await customerSelect.click();
        const listed = page.waitForResponse((r) => r.url().includes('/api/customers') && r.ok());
        await page.getByPlaceholder('Search...').fill('');
        await listed;

        const otherCustomerName = await page.evaluate(async (currentId) => {
            const token = localStorage.getItem('api_token') || '';
            const json = await (await fetch('/api/customers?per_page=5', {
                headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
            })).json();
            const other = (json.data || []).find((c: { id: number }) => Number(c.id) !== currentId);
            return String(other?.name ?? '');
        }, fixtures.customerId);
        expect(otherCustomerName).not.toBe('');
        await page.getByRole('button', { name: otherCustomerName, exact: true }).click();

        await expect(page.getByText('No allocations added yet.')).toBeVisible();
    });
});
