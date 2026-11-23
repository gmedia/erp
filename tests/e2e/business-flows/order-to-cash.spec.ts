import { expect, Page, test } from '@playwright/test';

import { login } from '../helpers';
import { apiGet, getFiscalYearId, readReportTotals, toNumber } from './helpers';

/**
 * Order-to-cash (issue #355).
 *
 * Customer invoice -> partial receipt -> credit note -> settlement.
 * Write steps go through the API: the invoice, receipt, and credit-note UI
 * selectors are not verified for this flow, and #355 allows the API for
 * prerequisites and for reading numbers. Assertions are before/after deltas.
 */

interface InvoiceAmounts {
    grand_total: number;
    amount_received: number;
    credit_note_amount: number;
    amount_due: number;
}

interface ArRow {
    customer_invoice: { id: number };
    amounts: Record<string, string>;
}

const INVOICE_TOTAL = 1000000;
const RECEIPT_PART = 400000;
const CREDIT_NOTE_AMOUNT = 100000;

async function authHeaders(page: Page): Promise<Record<string, string>> {
    const token = await page.evaluate(() => localStorage.getItem('api_token'));

    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
    };
}

async function postJson<T = Record<string, unknown>>(
    page: Page,
    path: string,
    body: unknown,
): Promise<{ status: number; json: T }> {
    const response = await page.request.post(path, {
        headers: await authHeaders(page),
        data: body,
    });

    return { status: response.status(), json: (await response.json()) as T };
}

async function firstId(page: Page, path: string): Promise<number> {
    const body = await apiGet<{ data?: Array<{ id: number }> }>(page, path);
    const row = body.data?.[0];
    expect(row, `${path} must return at least one row`).toBeTruthy();

    return Number(row?.id);
}

async function readOutstandingAmounts(
    page: Page,
    invoiceId: number,
): Promise<InvoiceAmounts> {
    const body = await apiGet<{ data: ArRow[] }>(
        page,
        '/api/reports/ar-outstanding?per_page=100',
    );
    const row = body.data.find(
        (item) => item.customer_invoice.id === invoiceId,
    );
    expect(
        row,
        `invoice ${invoiceId} must appear in AR outstanding`,
    ).toBeTruthy();
    const amounts = row?.amounts ?? {};

    return {
        grand_total: toNumber(amounts.grand_total),
        amount_received: toNumber(amounts.amount_received),
        credit_note_amount: toNumber(amounts.credit_note_amount),
        amount_due: toNumber(amounts.amount_due),
    };
}

async function readInvoiceRecord(
    page: Page,
    invoiceId: number,
): Promise<InvoiceAmounts & { status: string }> {
    const body = await apiGet<{ data: Record<string, unknown> }>(
        page,
        `/api/customer-invoices/${invoiceId}`,
    );
    const record = body.data;

    return {
        status: String(record.status ?? ''),
        grand_total: toNumber(record.grand_total),
        amount_received: toNumber(record.amount_received),
        credit_note_amount: toNumber(record.credit_note_amount),
        amount_due: toNumber(record.amount_due),
    };
}

interface O2cRun {
    invoiceId: number;
    revenueBefore: number;
    revenueAfter: number;
    afterInvoice: InvoiceAmounts;
    afterReceipt: InvoiceAmounts;
    afterCredit: InvoiceAmounts;
    settled: InvoiceAmounts & { status: string };
}

async function runOrderToCash(page: Page): Promise<O2cRun> {
    await login(page, undefined, undefined, { requireDashboard: false });

    const fiscalYearId = await getFiscalYearId(page);
    const customerId = await firstId(page, '/api/customers?per_page=1');
    const branchId = await firstId(page, '/api/branches?per_page=1');
    const bankAccountId = await firstId(page, '/api/accounts?per_page=1');
    const today = new Date().toISOString().slice(0, 10);
    const due = new Date(Date.now() + 30 * 24 * 3600 * 1000)
        .toISOString()
        .slice(0, 10);
    const description = `E2E O2C ${Date.now()}`;
    const lineItem = {
        account_id: bankAccountId,
        description,
        quantity: 1,
        unit_price: INVOICE_TOTAL,
        tax_percent: 0,
    };
    const header = {
        customer_id: customerId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        invoice_date: today,
        due_date: due,
        currency: 'IDR',
    };

    const before = await readReportTotals(page, fiscalYearId);

    const invoice = await postJson<{ data: { id: number } }>(
        page,
        '/api/customer-invoices',
        { ...header, status: 'draft', items: [lineItem] },
    );
    expect(invoice.status).toBeLessThan(400);
    const invoiceId = invoice.json.data.id;

    const sent = await page.request.put(`/api/customer-invoices/${invoiceId}`, {
        headers: await authHeaders(page),
        data: { ...header, status: 'sent', items: [lineItem] },
    });
    expect(sent.status()).toBeLessThan(400);

    const afterInvoice = await readOutstandingAmounts(page, invoiceId);
    expect(afterInvoice.grand_total).toBeCloseTo(INVOICE_TOTAL, 2);
    expect(afterInvoice.amount_received).toBeCloseTo(0, 2);
    expect(afterInvoice.amount_due).toBeCloseTo(INVOICE_TOTAL, 2);

    const receipt = await postJson(page, '/api/ar-receipts', {
        customer_id: customerId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        receipt_date: today,
        payment_method: 'bank_transfer',
        bank_account_id: bankAccountId,
        currency: 'IDR',
        total_amount: RECEIPT_PART,
        status: 'confirmed',
        allocations: [
            { customer_invoice_id: invoiceId, allocated_amount: RECEIPT_PART },
        ],
    });
    expect(receipt.status).toBeLessThan(400);

    const afterReceipt = await readOutstandingAmounts(page, invoiceId);
    expect(
        afterReceipt.amount_received - afterInvoice.amount_received,
    ).toBeCloseTo(RECEIPT_PART, 2);
    expect(afterReceipt.amount_due - afterInvoice.amount_due).toBeCloseTo(
        -RECEIPT_PART,
        2,
    );

    const creditNote = await postJson<{ data: { id: number } }>(
        page,
        '/api/credit-notes',
        {
            customer_id: customerId,
            customer_invoice_id: invoiceId,
            branch_id: branchId,
            fiscal_year_id: fiscalYearId,
            credit_note_date: today,
            reason: 'return',
            status: 'confirmed',
            items: [
                {
                    account_id: bankAccountId,
                    description: `CN ${description}`,
                    quantity: 1,
                    unit_price: CREDIT_NOTE_AMOUNT,
                    tax_percent: 0,
                },
            ],
        },
    );
    expect(creditNote.status).toBeLessThan(400);

    const applied = await postJson(
        page,
        `/api/credit-notes/${creditNote.json.data.id}/apply`,
        {},
    );
    expect(applied.status).toBeLessThan(400);

    const afterCredit = await readOutstandingAmounts(page, invoiceId);
    expect(
        afterCredit.credit_note_amount - afterReceipt.credit_note_amount,
    ).toBeCloseTo(CREDIT_NOTE_AMOUNT, 2);
    expect(afterCredit.amount_due - afterReceipt.amount_due).toBeCloseTo(
        -CREDIT_NOTE_AMOUNT,
        2,
    );

    const settle = await postJson(page, '/api/ar-receipts', {
        customer_id: customerId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        receipt_date: today,
        payment_method: 'bank_transfer',
        bank_account_id: bankAccountId,
        currency: 'IDR',
        total_amount: afterCredit.amount_due,
        status: 'confirmed',
        allocations: [
            {
                customer_invoice_id: invoiceId,
                allocated_amount: afterCredit.amount_due,
            },
        ],
    });
    expect(settle.status).toBeLessThan(400);

    const settled = await readInvoiceRecord(page, invoiceId);
    const after = await readReportTotals(page, fiscalYearId);

    return {
        invoiceId,
        revenueBefore: before.revenue,
        revenueAfter: after.revenue,
        afterInvoice,
        afterReceipt,
        afterCredit,
        settled,
    };
}

test.describe('Order-to-cash: invoice sampai pelunasan', () => {
    test.setTimeout(300000);

    test('invoice -> receipt sebagian -> credit note -> pelunasan mengosongkan piutang', async ({
        page,
    }) => {
        const run = await runOrderToCash(page);

        expect(run.settled.amount_received).toBeCloseTo(
            INVOICE_TOTAL - CREDIT_NOTE_AMOUNT,
            2,
        );
        expect(run.settled.credit_note_amount).toBeCloseTo(
            CREDIT_NOTE_AMOUNT,
            2,
        );
        expect(run.settled.amount_due).toBeCloseTo(0, 2);
        expect(run.settled.status).toBe('paid');
    });

    test('pelunasan invoice menaikkan pendapatan sebesar invoice dikurangi credit note', async ({
        page,
    }) => {
        test.fail(true, 'invoice tidak memposting revenue (#340, W7-02)');

        const run = await runOrderToCash(page);

        expect(run.revenueAfter - run.revenueBefore).toBeCloseTo(
            INVOICE_TOTAL - CREDIT_NOTE_AMOUNT,
            2,
        );
    });
});
