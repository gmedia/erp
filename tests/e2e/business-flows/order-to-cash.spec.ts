import { expect, Page, test } from '@playwright/test';

import { login } from '../helpers';
import {
    accountId,
    apiGet,
    firstId,
    getFiscalYearId,
    JournalEntryRecord,
    netForAccount,
    readJournalByReference,
    readReportTotals,
    sendJson,
    toNumber,
} from './helpers';

/**
 * Order-to-cash (issue #355).
 *
 * Customer invoice -> partial receipt -> credit note -> settlement.
 * Invoice is created as draft and moved to sent (the status that posts the
 * AR journal). Receipt, credit note, and apply are API prerequisites; all
 * amounts are read back from the API and the journals are checked.
 */

const REVENUE_ACCOUNT_CODE = '41000';
const CASH_ACCOUNT_CODE = '11110';
const AR_ACCOUNT_CODE = '11200';

const INVOICE_TOTAL = 1000000;
const RECEIPT_PART = 400000;
const CREDIT_NOTE_AMOUNT = 100000;

interface InvoiceAmounts {
    grand_total: number;
    amount_received: number;
    credit_note_amount: number;
    amount_due: number;
}

interface ArRow {
    customer_invoice: { id: number; invoice_number: string };
    amounts: Record<string, string>;
}

async function readOutstanding(
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

async function readInvoice(
    page: Page,
    invoiceId: number,
): Promise<InvoiceAmounts & { status: string; invoice_number: string }> {
    const body = await apiGet<{ data: Record<string, unknown> }>(
        page,
        `/api/customer-invoices/${invoiceId}`,
    );
    const record = body.data;

    return {
        status: String(record.status ?? ''),
        invoice_number: String(record.invoice_number ?? ''),
        grand_total: toNumber(record.grand_total),
        amount_received: toNumber(record.amount_received),
        credit_note_amount: toNumber(record.credit_note_amount),
        amount_due: toNumber(record.amount_due),
    };
}

interface O2cRun {
    invoiceId: number;
    invoiceNumber: string;
    revenueAccountId: number;
    arAccountId: number;
    cashAccountId: number;
    invoiceJournal: JournalEntryRecord;
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
    const revenueAccountId = await accountId(page, REVENUE_ACCOUNT_CODE);
    const cashAccountId = await accountId(page, CASH_ACCOUNT_CODE);
    const arAccountId = await accountId(page, AR_ACCOUNT_CODE);
    const today = new Date().toISOString().slice(0, 10);
    const due = new Date(Date.now() + 30 * 24 * 3600 * 1000)
        .toISOString()
        .slice(0, 10);
    const description = `E2E O2C ${Date.now()}`;
    const lineItem = {
        account_id: revenueAccountId,
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

    const revenueBefore = (await readReportTotals(page, fiscalYearId)).revenue;

    const created = await sendJson(page, 'POST', '/api/customer-invoices', {
        ...header,
        status: 'draft',
        items: [lineItem],
    });
    expect(created.status).toBeLessThan(400);
    const invoiceId = Number((created.json.data as { id: number }).id);

    const sent = await sendJson(
        page,
        'PUT',
        `/api/customer-invoices/${invoiceId}`,
        { ...header, status: 'sent', items: [lineItem] },
    );
    expect(sent.status).toBeLessThan(400);

    const invoice = await readInvoice(page, invoiceId);
    const invoiceJournal = await readJournalByReference(
        page,
        invoice.invoice_number,
    );

    const afterInvoice = await readOutstanding(page, invoiceId);
    expect(afterInvoice.grand_total).toBeCloseTo(INVOICE_TOTAL, 2);
    expect(afterInvoice.amount_due).toBeCloseTo(INVOICE_TOTAL, 2);

    const receipt = await sendJson(page, 'POST', '/api/ar-receipts', {
        customer_id: customerId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        receipt_date: today,
        payment_method: 'bank_transfer',
        bank_account_id: cashAccountId,
        currency: 'IDR',
        total_amount: RECEIPT_PART,
        status: 'confirmed',
        allocations: [
            { customer_invoice_id: invoiceId, allocated_amount: RECEIPT_PART },
        ],
    });
    expect(receipt.status).toBeLessThan(400);

    const afterReceipt = await readOutstanding(page, invoiceId);
    expect(
        afterReceipt.amount_received - afterInvoice.amount_received,
    ).toBeCloseTo(RECEIPT_PART, 2);
    expect(afterReceipt.amount_due - afterInvoice.amount_due).toBeCloseTo(
        -RECEIPT_PART,
        2,
    );

    const creditNote = await sendJson(page, 'POST', '/api/credit-notes', {
        customer_id: customerId,
        customer_invoice_id: invoiceId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        credit_note_date: today,
        reason: 'return',
        status: 'confirmed',
        items: [
            {
                account_id: revenueAccountId,
                description: `CN ${description}`,
                quantity: 1,
                unit_price: CREDIT_NOTE_AMOUNT,
                tax_percent: 0,
            },
        ],
    });
    expect(creditNote.status).toBeLessThan(400);
    const creditNoteId = Number((creditNote.json.data as { id: number }).id);

    const applied = await sendJson(
        page,
        'POST',
        `/api/credit-notes/${creditNoteId}/apply`,
        {},
    );
    expect(applied.status).toBeLessThan(400);

    const afterCredit = await readOutstanding(page, invoiceId);
    expect(
        afterCredit.credit_note_amount - afterReceipt.credit_note_amount,
    ).toBeCloseTo(CREDIT_NOTE_AMOUNT, 2);
    expect(afterCredit.amount_due - afterReceipt.amount_due).toBeCloseTo(
        -CREDIT_NOTE_AMOUNT,
        2,
    );

    const settle = await sendJson(page, 'POST', '/api/ar-receipts', {
        customer_id: customerId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        receipt_date: today,
        payment_method: 'bank_transfer',
        bank_account_id: cashAccountId,
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

    const settled = await readInvoice(page, invoiceId);
    const revenueAfter = (await readReportTotals(page, fiscalYearId)).revenue;

    return {
        invoiceId,
        invoiceNumber: invoice.invoice_number,
        revenueAccountId,
        arAccountId,
        cashAccountId,
        invoiceJournal,
        revenueBefore,
        revenueAfter,
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

    test('jurnal invoice posted: Dr AR 11200 / Cr Pendapatan 41000 sebesar total', async ({
        page,
    }) => {
        const run = await runOrderToCash(page);

        expect(run.invoiceJournal.status).toBe('posted');
        expect(netForAccount(run.invoiceJournal, run.arAccountId)).toBeCloseTo(
            INVOICE_TOTAL,
            2,
        );
        expect(
            netForAccount(run.invoiceJournal, run.revenueAccountId),
        ).toBeCloseTo(-INVOICE_TOTAL, 2);
    });

    test('pelunasan invoice menaikkan pendapatan sebesar invoice dikurangi credit note', async ({
        page,
    }) => {
        test.fail(
            true,
            'invoice tidak memposting revenue ke 41000 (#340, W7-02)',
        );

        const run = await runOrderToCash(page);

        expect(run.revenueAfter - run.revenueBefore).toBeCloseTo(
            INVOICE_TOTAL - CREDIT_NOTE_AMOUNT,
            2,
        );
    });
});
