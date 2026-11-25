import { expect, Page, test } from '@playwright/test';

import { expectResponseOk, login, searchAndWaitForApi } from '../helpers';

interface SeedResult {
    supplierId: number;
    branchId: number;
    fiscalYearId: number;
    accountId: number;
    sentBillNumber: string;
    draftBillNumber: string;
    otherSupplierBillNumber: string;
    paymentId: number;
    paymentNumber: string;
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

        const otherSupplierId = async (currentId: number): Promise<number> => {
            const json = await (await fetch('/api/suppliers?per_page=5', { headers })).json();
            const other = (json.data || []).find((s: { id: number }) => Number(s.id) !== currentId);
            if (!other) {
                throw new Error('supplier kedua tidak ditemukan');
            }
            return Number(other.id);
        };

        const [supplierId, branchId, fiscalYearId, accountId] = await Promise.all([
            firstId('/api/suppliers?per_page=1'),
            firstId('/api/branches?per_page=1'),
            firstId('/api/fiscal-years?per_page=1'),
            postableAccountId(),
        ]);
        const otherId = await otherSupplierId(supplierId);

        const today = new Date().toISOString().slice(0, 10);
        const dueDate = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
        const items = [{
            account_id: accountId,
            description: 'Allocation select test',
            quantity: 1,
            unit_price: 1000000,
            discount_percent: 0,
            tax_percent: 0,
        }];

        const createBill = async (targetSupplierId: number, status: 'draft' | 'confirmed') => {
            const created = await (await fetch('/api/supplier-bills', {
                method: 'POST',
                headers,
                body: JSON.stringify({
                    supplier_id: targetSupplierId,
                    branch_id: branchId,
                    fiscal_year_id: fiscalYearId,
                    bill_date: today,
                    due_date: dueDate,
                    currency: 'IDR',
                    status,
                    items,
                }),
            })).json();
            const number = String(created?.data?.bill_number ?? '');
            if (!number) {
                throw new Error(`bill gagal dibuat untuk supplier ${String(targetSupplierId)}`);
            }
            return { id: Number(created.data.id), number };
        };

        const sent = await createBill(supplierId, 'confirmed');
        const draft = await createBill(supplierId, 'draft');
        const otherBill = await createBill(otherId, 'confirmed');

        const paymentResponse = await fetch('/api/ap-payments', {
            method: 'POST',
            headers,
            body: JSON.stringify({
                supplier_id: supplierId,
                branch_id: branchId,
                fiscal_year_id: fiscalYearId,
                payment_date: today,
                payment_method: 'bank_transfer',
                bank_account_id: accountId,
                currency: 'IDR',
                total_amount: 300000,
                status: 'draft',
                allocations: [{ supplier_bill_id: sent.id, allocated_amount: 300000 }],
            }),
        });
        const paymentPayload = await paymentResponse.json();

        return {
            supplierId,
            branchId,
            fiscalYearId,
            accountId,
            sentBillNumber: sent.number,
            draftBillNumber: draft.number,
            otherSupplierBillNumber: otherBill.number,
            paymentId: Number(paymentPayload?.data?.id),
            paymentNumber: String(paymentPayload?.data?.payment_number ?? ''),
        };
    });
}

async function openPaymentEdit(page: Page, paymentNumber: string): Promise<void> {
    await searchAndWaitForApi(page, page.getByPlaceholder(/search/i), paymentNumber, '/api/ap-payments');
    const row = page.locator('tbody tr').filter({ hasText: paymentNumber }).first();
    await row.getByRole('button').last().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
}

test.describe('AP Payment allocation selects', () => {
    test.describe.configure({ mode: 'serial' });

    let fixtures: SeedResult;

    test.beforeEach(async ({ page }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        fixtures ??= await seedAllocationFixtures(page);
        const listed = page.waitForResponse(
            (r) => r.url().includes('/api/ap-payments') && r.request().method() === 'GET',
            { timeout: 30000 },
        );
        await page.goto('/ap-payments');
        await expectResponseOk(await listed);
    });

    test('edit dokumen lama menampilkan nomor bill', async ({ page }) => {
        expect(fixtures.paymentNumber).not.toBe('');

        await openPaymentEdit(page, fixtures.paymentNumber);
        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog).toBeVisible();
        await expect(formDialog.getByRole('row').filter({ hasText: fixtures.sentBillNumber })).toHaveCount(1);
    });

    test('hanya bill terbuka milik supplier terpilih yang ditawarkan', async ({ page }) => {
        await openPaymentEdit(page, fixtures.paymentNumber);
        const formDialog = page.getByRole('dialog').first();
        await formDialog.getByRole('button', { name: 'Add Item' }).click();
        const allocationDialog = page.getByRole('dialog').last();

        const listed = page.waitForResponse((r) => r.url().includes('/api/supplier-bills') && r.request().method() === 'GET');
        await allocationDialog.getByRole('combobox', { name: /bill/i }).first().click();
        await expectResponseOk(await listed);

        for (const hidden of [fixtures.draftBillNumber, fixtures.otherSupplierBillNumber]) {
            await expect(page.getByRole('button', { name: new RegExp(hidden) })).toHaveCount(0);
        }
        await expect(page.getByRole('button', { name: new RegExp(fixtures.sentBillNumber) })).toHaveCount(1);
    });

    test('ganti supplier di header mengosongkan baris alokasi', async ({ page }) => {
        await openPaymentEdit(page, fixtures.paymentNumber);
        const formDialog = page.getByRole('dialog').first();
        await expect(formDialog.getByRole('row').filter({ hasText: fixtures.sentBillNumber })).toHaveCount(1);

        const supplierSelect = formDialog.getByRole('combobox', { name: 'Supplier' }).first();
        await supplierSelect.click();
        const listed = page.waitForResponse((r) => r.url().includes('/api/suppliers') && r.ok());
        await page.getByPlaceholder('Search...').fill('');
        await listed;

        const otherSupplierName = await page.evaluate(async (currentId) => {
            const token = localStorage.getItem('api_token') || '';
            const json = await (await fetch('/api/suppliers?per_page=5', {
                headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
            })).json();
            const other = (json.data || []).find((s: { id: number }) => Number(s.id) !== currentId);
            return String(other?.name ?? '');
        }, fixtures.supplierId);
        expect(otherSupplierName).not.toBe('');
        await page.getByRole('button', { name: otherSupplierName, exact: true }).click();

        await expect(formDialog.getByText('No items added yet.')).toBeVisible();
    });
});
