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
    readStockMovementFor,
    readStockOnHandFor,
    sendJson,
    toNumber,
} from './helpers';

/**
 * Procure-to-pay (issue #355).
 *
 * Purchase request -> approval -> purchase order -> goods receipt confirmed ->
 * supplier bill -> supplier payment. Checks PO received qty, stock on hand,
 * bill amount due and status, and the posted journal of each document, plus
 * that the trial balance and balance sheet stay balanced. All amounts are
 * before/after deltas.
 *
 * Known defects are asserted with test.fail() and linked to their issue:
 * #367 (journal on POST confirmed).
 */

const PO_QTY = 4;
const UNIT_PRICE = 250000;
const INVENTORY_ACCOUNT_CODE = '11300';
const AP_ACCOUNT_CODE = '21100';
const GRIR_ACCOUNT_CODE = '21150';
const EXPENSE_ACCOUNT_CODE = '51000';
const CASH_ACCOUNT_CODE = '11110';

interface PoItem {
    id: number;
    quantity: string;
    quantity_received: string;
}

interface PurchaseOrder {
    id: number;
    status: string;
    items: PoItem[];
}

async function readPurchaseOrder(
    page: Page,
    poId: number,
): Promise<PurchaseOrder> {
    const body = await apiGet<{ data: PurchaseOrder }>(
        page,
        `/api/purchase-orders/${poId}`,
    );

    return body.data;
}

async function readBill(
    page: Page,
    billId: number,
): Promise<{ status: string; amount_due: number }> {
    const body = await apiGet<{ data: { status: string; amount_due: string } }>(
        page,
        `/api/supplier-bills/${billId}`,
    );

    return {
        status: body.data.status,
        amount_due: toNumber(body.data.amount_due),
    };
}

interface P2pRun {
    poId: number;
    poReceivedQty: number;
    stockDelta: number;
    grMovements: Array<{ movement_type: string; quantity_in: string }>;
    billId: number;
    billReference: string;
    billAmountDueAfterCreate: number;
    billStatusAfterPayment: string;
    billAmountDueAfterPayment: number;
    grJournal: JournalEntryRecord;
    billJournal: JournalEntryRecord;
    paymentJournal: JournalEntryRecord;
    accounts: {
        inventory: number;
        ap: number;
        grir: number;
        expense: number;
        cash: number;
    };
    trialBalanceGap: number;
    balanceSheetGap: number;
}

async function createPurchaseOrderViaUi(page: Page): Promise<number> {
    await page.goto('/purchase-orders');
    await page.getByRole('button', { name: /add/i }).first().click();

    const dialog = page.getByRole('dialog', {
        name: /Add New Purchase Order/i,
    });
    await expect(dialog).toBeVisible();

    await dialog.getByRole('combobox', { name: 'Supplier' }).click();
    await page
        .getByRole('dialog')
        .last()
        .getByRole('listitem')
        .first()
        .getByRole('button')
        .click();

    await dialog.getByRole('combobox', { name: 'Warehouse' }).click();
    await page
        .getByRole('dialog')
        .last()
        .getByRole('listitem')
        .first()
        .getByRole('button')
        .click();

    await dialog.getByRole('button', { name: 'Add Item' }).click();
    const itemDialog = page.getByRole('dialog', { name: /Add Item/i });
    await expect(itemDialog).toBeVisible();
    await itemDialog.getByRole('combobox', { name: 'Product' }).click();
    await page
        .getByRole('dialog')
        .last()
        .getByRole('listitem')
        .first()
        .getByRole('button')
        .click();
    await itemDialog.getByRole('combobox', { name: 'Unit' }).click();
    await page
        .getByRole('dialog')
        .last()
        .getByRole('listitem')
        .first()
        .getByRole('button')
        .click();
    await itemDialog.getByLabel(/Quantity/i).fill(String(PO_QTY));
    await itemDialog.getByLabel(/Unit Price/i).fill(String(UNIT_PRICE));
    await itemDialog.getByRole('button', { name: 'Save Item' }).click();
    await expect(itemDialog).toBeHidden();

    const createResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/api/purchase-orders') &&
            response.request().method() === 'POST',
    );
    await dialog.getByRole('button', { name: 'Add', exact: true }).click();
    const created = await (await createResponse).json();
    await expect(dialog).toBeHidden();

    const poId = Number((created.data as { id: number }).id);
    expect(poId, 'PO created via UI must return an id').toBeGreaterThan(0);

    return poId;
}

async function submitAndApprovePurchaseOrder(
    page: Page,
    poId: number,
): Promise<void> {
    const transitions = await apiGet<{
        data: { available_transitions: Array<{ id: number; name: string }> };
    }>(page, `/api/entity-states/purchase_order/${poId}`);
    const submit = transitions.data.available_transitions.find(
        (item) => item.name === 'Submit',
    );
    expect(submit, 'PO draft must offer the Submit transition').toBeTruthy();

    const submitted = await sendJson(
        page,
        'POST',
        `/api/entity-states/purchase_order/${poId}/transition`,
        { transition_id: submit?.id },
    );
    expect(submitted.status).toBeLessThan(400);

    const inbox = await apiGet<{
        pending: Array<{ request: { id: number; approvable_id: number } }>;
    }>(page, '/api/my-approvals');
    const step = inbox.pending.find(
        (item) => item.request.approvable_id === poId,
    );
    expect(
        step,
        `approval request for PO ${poId} must be in my-approvals`,
    ).toBeTruthy();

    const approved = await sendJson(
        page,
        'POST',
        `/api/my-approvals/${step?.request.id}/approve`,
        { comments: 'E2E P2P' },
    );
    expect(approved.status).toBeLessThan(400);
}

async function createGoodsReceiptViaUi(
    page: Page,
    ctx: {
        poId: number;
        poItemId: number;
        warehouseId: number;
        productId: number;
        unitId: number;
    },
): Promise<string> {
    await page.goto('/goods-receipts');
    await page.getByRole('button', { name: /add/i }).first().click();

    const dialog = page.getByRole('dialog', {
        name: /Add New Goods Receipt/i,
    });
    await expect(dialog).toBeVisible();

    await dialog.getByRole('combobox', { name: 'Purchase Order' }).click();
    await page
        .getByRole('dialog')
        .last()
        .getByPlaceholder('Search...')
        .fill(String(ctx.poId));
    await page
        .getByRole('dialog')
        .last()
        .getByRole('listitem')
        .first()
        .getByRole('button')
        .click();

    await dialog.getByRole('combobox', { name: 'Warehouse' }).click();
    await page
        .getByRole('dialog')
        .last()
        .getByRole('listitem')
        .first()
        .getByRole('button')
        .click();

    await dialog.getByRole('button', { name: 'Add Item' }).click();
    const itemDialog = page.getByRole('dialog', { name: /Add Item/i });
    await expect(itemDialog).toBeVisible();
    await itemDialog.getByLabel('PO Item ID').fill(String(ctx.poItemId));
    await itemDialog.getByRole('combobox', { name: 'Product' }).click();
    await page
        .getByRole('dialog')
        .last()
        .getByRole('listitem')
        .first()
        .getByRole('button')
        .click();
    await itemDialog.getByRole('combobox', { name: 'Unit' }).click();
    await page
        .getByRole('dialog')
        .last()
        .getByRole('listitem')
        .first()
        .getByRole('button')
        .click();
    await itemDialog.getByLabel('Quantity Received').fill(String(PO_QTY));
    await itemDialog.getByLabel('Quantity Accepted').fill(String(PO_QTY));
    await itemDialog.getByLabel('Unit Price').fill(String(UNIT_PRICE));
    await itemDialog.getByRole('button', { name: 'Save Item' }).click();
    await expect(itemDialog).toBeHidden();

    const createResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/api/goods-receipts') &&
            response.request().method() === 'POST',
    );
    await dialog.getByRole('button', { name: 'Add', exact: true }).click();
    const created = await (await createResponse).json();
    await expect(dialog).toBeHidden();

    const createdData = created.data as { id: number; gr_number: string };
    const grNumber = String(createdData.gr_number);

    const row = page.getByRole('row').filter({ hasText: grNumber }).first();
    await row.getByRole('button', { name: 'Actions' }).click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
    const editDialog = page.getByRole('dialog', {
        name: /Edit Goods Receipt/i,
    });
    await expect(editDialog).toBeVisible();
    await editDialog.getByRole('combobox', { name: 'Status' }).click();
    await page.getByRole('option', { name: 'Confirmed', exact: true }).click();
    const updateResponse = page.waitForResponse(
        (response) =>
            response.url().includes(`/api/goods-receipts/${createdData.id}`) &&
            response.request().method() === 'PUT',
    );
    await editDialog
        .getByRole('button', { name: 'Update', exact: true })
        .click();
    await updateResponse;
    await expect(editDialog).toBeHidden();

    return grNumber;
}

async function runProcureToPay(page: Page): Promise<P2pRun> {
    await login(page, undefined, undefined, { requireDashboard: false });

    const fiscalYearId = await getFiscalYearId(page);
    const supplierId = await firstId(page, '/api/suppliers?per_page=1');
    const branchId = await firstId(page, '/api/branches?per_page=1');
    const warehouseId = await firstId(page, '/api/warehouses?per_page=1');
    const productId = await firstId(page, '/api/products?per_page=1');
    const unitId = await firstId(page, '/api/units?per_page=1');
    const accounts = {
        inventory: await accountId(page, INVENTORY_ACCOUNT_CODE),
        ap: await accountId(page, AP_ACCOUNT_CODE),
        grir: await accountId(page, GRIR_ACCOUNT_CODE),
        expense: await accountId(page, EXPENSE_ACCOUNT_CODE),
        cash: await accountId(page, CASH_ACCOUNT_CODE),
    };
    const today = new Date().toISOString().slice(0, 10);

    const stockBefore = await readStockOnHandFor(page, productId, warehouseId);

    const poId = await createPurchaseOrderViaUi(page);
    await submitAndApprovePurchaseOrder(page, poId);

    const poLine = (await readPurchaseOrder(page, poId)).items[0];
    expect(poLine, `PO ${poId} must have one item`).toBeTruthy();

    const grNumber = await createGoodsReceiptViaUi(page, {
        poId,
        poItemId: poLine.id,
        warehouseId,
        productId,
        unitId,
    });

    const poReceived = await readPurchaseOrder(page, poId);
    const receivedLine = poReceived.items[0];
    const stockAfter = await readStockOnHandFor(page, productId, warehouseId);
    const grMovements = await readStockMovementFor(page, grNumber);
    const grJournal = await readJournalByReference(page, grNumber);

    const billTotal = PO_QTY * UNIT_PRICE;
    const billItems = [
        {
            account_id: accounts.expense,
            description: `E2E P2P ${Date.now()}`,
            quantity: PO_QTY,
            unit_price: UNIT_PRICE,
            discount_percent: 0,
            tax_percent: 0,
        },
    ];
    const billHeader = {
        supplier_id: supplierId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        bill_date: today,
        due_date: today,
        currency: 'IDR',
        purchase_order_id: poId,
    };
    const bill = await sendJson(page, 'POST', '/api/supplier-bills', {
        ...billHeader,
        status: 'draft',
        items: billItems,
    });
    expect(bill.status).toBeLessThan(400);
    const billId = Number((bill.json.data as { id: number }).id);
    const billReference = String(
        (bill.json.data as { bill_number: string }).bill_number,
    );
    const billConfirm = await sendJson(
        page,
        'PUT',
        `/api/supplier-bills/${billId}`,
        { ...billHeader, status: 'confirmed', items: billItems },
    );
    expect(billConfirm.status).toBeLessThan(400);
    const billAfterCreate = await readBill(page, billId);
    const billJournal = await readJournalByReference(page, billReference);

    const paymentHeader = {
        supplier_id: supplierId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        payment_date: today,
        payment_method: 'bank_transfer',
        bank_account_id: accounts.cash,
        currency: 'IDR',
        total_amount: billTotal,
        allocations: [
            { supplier_bill_id: billId, allocated_amount: billTotal },
        ],
    };
    const payment = await sendJson(page, 'POST', '/api/ap-payments', {
        ...paymentHeader,
        status: 'draft',
    });
    expect(payment.status).toBeLessThan(400);
    const paymentId = Number((payment.json.data as { id: number }).id);
    const paymentReference = String(
        (payment.json.data as { payment_number: string }).payment_number,
    );
    const paymentConfirm = await sendJson(
        page,
        'PUT',
        `/api/ap-payments/${paymentId}`,
        { ...paymentHeader, status: 'confirmed' },
    );
    expect(paymentConfirm.status).toBeLessThan(400);
    const paymentJournal = await readJournalByReference(page, paymentReference);

    const billPaid = await readBill(page, billId);
    const reports = await readReportTotals(page, fiscalYearId);

    return {
        poId,
        poReceivedQty: toNumber(receivedLine?.quantity_received),
        stockDelta: stockAfter - stockBefore,
        grMovements,
        billId,
        billReference,
        billAmountDueAfterCreate: billAfterCreate.amount_due,
        billStatusAfterPayment: billPaid.status,
        billAmountDueAfterPayment: billPaid.amount_due,
        grJournal,
        billJournal,
        paymentJournal,
        accounts,
        trialBalanceGap: reports.trialDebit - reports.trialCredit,
        balanceSheetGap:
            reports.assets - (reports.liabilities + reports.equity),
    };
}

async function submitAndApprovePurchaseRequest(
    page: Page,
): Promise<{ prId: number; status: string }> {
    const branchId = await firstId(page, '/api/branches?per_page=1');
    const productId = await firstId(page, '/api/products?per_page=1');
    const unitId = await firstId(page, '/api/units?per_page=1');
    const today = new Date().toISOString().slice(0, 10);

    const created = await sendJson(page, 'POST', '/api/purchase-requests', {
        branch_id: branchId,
        request_date: today,
        priority: 'normal',
        status: 'draft',
        notes: 'E2E P2P',
        items: [
            {
                product_id: productId,
                unit_id: unitId,
                quantity: PO_QTY,
                estimated_unit_price: UNIT_PRICE,
            },
        ],
    });
    expect(created.status).toBeLessThan(400);
    const prId = Number((created.json.data as { id: number }).id);

    const transitions = await apiGet<{
        data: { available_transitions: Array<{ id: number; name: string }> };
    }>(page, `/api/entity-states/purchase_request/${prId}`);
    const submit = transitions.data.available_transitions.find(
        (item) => item.name === 'Submit',
    );
    expect(submit, 'PR draft must offer the Submit transition').toBeTruthy();

    const submitted = await sendJson(
        page,
        'POST',
        `/api/entity-states/purchase_request/${prId}/transition`,
        { transition_id: submit?.id },
    );
    expect(submitted.status).toBeLessThan(400);

    const inbox = await apiGet<{
        pending: Array<{ request: { id: number; approvable_id: number } }>;
    }>(page, '/api/my-approvals');
    const step = inbox.pending.find(
        (item) => item.request.approvable_id === prId,
    );
    expect(
        step,
        `approval request for PR ${prId} must be in my-approvals`,
    ).toBeTruthy();

    const approved = await sendJson(
        page,
        'POST',
        `/api/my-approvals/${step?.request.id}/approve`,
        { comments: 'E2E P2P' },
    );
    expect(approved.status).toBeLessThan(400);

    const pr = await apiGet<{ data: { status: string } }>(
        page,
        `/api/purchase-requests/${prId}`,
    );

    return { prId, status: pr.data.status };
}

test.describe('Procure-to-pay: PR sampai pembayaran supplier', () => {
    test.setTimeout(300000);

    test('PR -> approval: PR submit lalu approve, status menjadi approved', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });

        const run = await submitAndApprovePurchaseRequest(page);

        expect(run.status).toBe('approved');
    });

    test('PO received qty, stok, bill, dan pembayaran sesuai nilai dokumen', async ({
        page,
    }) => {
        const run = await runProcureToPay(page);

        expect(run.poReceivedQty).toBe(PO_QTY);
        expect(run.stockDelta).toBeCloseTo(PO_QTY, 2);
        expect(run.grMovements).toHaveLength(1);
        expect(run.grMovements[0].movement_type).toBe('goods_receipt');
        expect(toNumber(run.grMovements[0].quantity_in)).toBe(PO_QTY);
        expect(run.billAmountDueAfterCreate).toBeCloseTo(
            PO_QTY * UNIT_PRICE,
            2,
        );
        expect(run.billAmountDueAfterPayment).toBeCloseTo(0, 2);
        expect(run.billStatusAfterPayment).toBe('paid');
    });

    test('jurnal GR, bill, dan pembayaran posted dan sesuai nilai dokumen', async ({
        page,
    }) => {
        const run = await runProcureToPay(page);
        const total = PO_QTY * UNIT_PRICE;

        for (const journal of [
            run.grJournal,
            run.billJournal,
            run.paymentJournal,
        ]) {
            expect(journal.status).toBe('posted');
        }
        expect(
            netForAccount(run.grJournal, run.accounts.inventory),
        ).toBeCloseTo(total, 2);
        expect(netForAccount(run.grJournal, run.accounts.grir)).toBeCloseTo(
            -total,
            2,
        );
        expect(
            netForAccount(run.billJournal, run.accounts.expense),
        ).toBeCloseTo(total, 2);
        expect(netForAccount(run.billJournal, run.accounts.grir)).toBeCloseTo(
            0,
            2,
        );
        expect(netForAccount(run.billJournal, run.accounts.ap)).toBeCloseTo(
            -total,
            2,
        );
        expect(netForAccount(run.paymentJournal, run.accounts.ap)).toBeCloseTo(
            total,
            2,
        );
        expect(
            netForAccount(run.paymentJournal, run.accounts.cash),
        ).toBeCloseTo(-total, 2);
    });

    test('bill dan pembayaran dengan status confirmed lewat POST langsung memposting jurnal', async ({
        page,
    }) => {
        await login(page, undefined, undefined, { requireDashboard: false });
        const fiscalYearId = await getFiscalYearId(page);
        const supplierId = await firstId(page, '/api/suppliers?per_page=1');
        const branchId = await firstId(page, '/api/branches?per_page=1');
        const expenseId = await accountId(page, EXPENSE_ACCOUNT_CODE);
        const today = new Date().toISOString().slice(0, 10);

        const bill = await sendJson(page, 'POST', '/api/supplier-bills', {
            supplier_id: supplierId,
            branch_id: branchId,
            fiscal_year_id: fiscalYearId,
            bill_date: today,
            due_date: today,
            currency: 'IDR',
            status: 'confirmed',
            items: [
                {
                    account_id: expenseId,
                    description: `E2E P2P direct ${Date.now()}`,
                    quantity: 1,
                    unit_price: 100000,
                    discount_percent: 0,
                    tax_percent: 0,
                },
            ],
        });
        expect(bill.status).toBeLessThan(400);
        const billNumber = String(
            (bill.json.data as { bill_number: string }).bill_number,
        );

        const journal = await readJournalByReference(page, billNumber);
        expect(journal.status).toBe('posted');
    });

    test('trial balance dan neraca tetap balance setelah siklus P2P', async ({
        page,
    }) => {
        const run = await runProcureToPay(page);

        expect(run.trialBalanceGap).toBeCloseTo(0, 2);
        expect(run.balanceSheetGap).toBeCloseTo(0, 2);
    });
});
