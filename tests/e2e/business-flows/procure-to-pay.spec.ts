import { expect, Page, test } from '@playwright/test';

import { login } from '../helpers';
import { apiGet, getFiscalYearId, readReportTotals, toNumber } from './helpers';

/**
 * Procure-to-pay (issue #355).
 *
 * Purchase order -> goods receipt confirmed -> supplier bill -> supplier
 * payment. Checks stock on hand, bill amount due / status, and that the trial
 * balance and balance sheet stay balanced. All numbers are before/after deltas.
 *
 * Write steps use the API (prerequisites, allowed by #355). The PO received
 * qty defect is tracked in #356 and asserted as test.fail().
 */

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

const PO_QTY = 4;
const UNIT_PRICE = 250000;

async function authHeaders(page: Page): Promise<Record<string, string>> {
    const token = await page.evaluate(() => localStorage.getItem('api_token'));

    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
    };
}

async function send(
    page: Page,
    method: 'POST' | 'PUT',
    path: string,
    body: unknown,
): Promise<{ status: number; json: Record<string, unknown> }> {
    const response = await page.request.fetch(path, {
        method,
        headers: await authHeaders(page),
        data: body,
    });

    return {
        status: response.status(),
        json: (await response.json().catch(() => ({}))) as Record<
            string,
            unknown
        >,
    };
}

async function firstId(page: Page, path: string): Promise<number> {
    const body = await apiGet<{ data?: Array<{ id: number }> }>(page, path);
    const row = body.data?.[0];
    expect(row, `${path} must return at least one row`).toBeTruthy();

    return Number(row?.id);
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

async function readStockOnHand(
    page: Page,
    productId: number,
    warehouseId: number,
): Promise<number> {
    const body = await apiGet<{ data: Array<{ balance_after: string }> }>(
        page,
        `/api/stock-movements?product_id=${productId}&warehouse_id=${warehouseId}&per_page=100`,
    );
    const latest = (body.data ?? [])[0];

    return latest ? toNumber(latest.balance_after) : 0;
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
    billId: number;
    billAmountDueAfterCreate: number;
    billStatusAfterPayment: string;
    billAmountDueAfterPayment: number;
    trialBalanceGap: number;
    balanceSheetGapDelta: number;
}

async function runProcureToPay(page: Page): Promise<P2pRun> {
    await login(page, undefined, undefined, { requireDashboard: false });

    const fiscalYearId = await getFiscalYearId(page);
    const supplierId = await firstId(page, '/api/suppliers?per_page=1');
    const branchId = await firstId(page, '/api/branches?per_page=1');
    const warehouseId = await firstId(page, '/api/warehouses?per_page=1');
    const productId = await firstId(page, '/api/products?per_page=1');
    const unitId = await firstId(page, '/api/units?per_page=1');
    const accountId = await firstId(page, '/api/accounts?per_page=1');
    const today = new Date().toISOString().slice(0, 10);

    const stockBefore = await readStockOnHand(page, productId, warehouseId);
    const reportsBefore = await readReportTotals(page, fiscalYearId);
    const gapBefore =
        reportsBefore.assets -
        (reportsBefore.liabilities + reportsBefore.equity);

    const po = await send(page, 'POST', '/api/purchase-orders', {
        supplier_id: supplierId,
        warehouse_id: warehouseId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        order_date: today,
        currency: 'IDR',
        status: 'draft',
        items: [
            {
                product_id: productId,
                unit_id: unitId,
                quantity: PO_QTY,
                unit_price: UNIT_PRICE,
                discount_percent: 0,
                tax_percent: 0,
            },
        ],
    });
    expect(po.status).toBeLessThan(400);
    const poId = Number((po.json.data as { id: number }).id);

    const poDraft = await readPurchaseOrder(page, poId);
    const poLine = poDraft.items[0];
    if (!poLine) {
        throw new Error(`PO ${poId} must have at least one item`);
    }
    expect(toNumber(poLine.quantity)).toBe(PO_QTY);
    expect(toNumber(poLine.quantity_received)).toBe(0);

    const grItems = [
        {
            purchase_order_item_id: poLine.id,
            product_id: productId,
            unit_id: unitId,
            quantity_received: PO_QTY,
            quantity_accepted: PO_QTY,
            unit_price: UNIT_PRICE,
        },
    ];
    const gr = await send(page, 'POST', '/api/goods-receipts', {
        purchase_order_id: poId,
        warehouse_id: warehouseId,
        branch_id: branchId,
        receipt_date: today,
        status: 'draft',
        items: grItems,
    });
    expect(gr.status).toBeLessThan(400);
    const grId = Number((gr.json.data as { id: number }).id);

    const grConfirm = await send(page, 'PUT', `/api/goods-receipts/${grId}`, {
        purchase_order_id: poId,
        warehouse_id: warehouseId,
        branch_id: branchId,
        receipt_date: today,
        status: 'confirmed',
        items: grItems,
    });
    expect(grConfirm.status).toBeLessThan(400);

    const poReceived = await readPurchaseOrder(page, poId);
    const receivedLine = poReceived.items[0];
    if (!receivedLine) {
        throw new Error(`PO ${poId} lost its item after GR confirm`);
    }
    const stockAfter = await readStockOnHand(page, productId, warehouseId);

    const billTotal = PO_QTY * UNIT_PRICE;
    const bill = await send(page, 'POST', '/api/supplier-bills', {
        supplier_id: supplierId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        bill_date: today,
        due_date: today,
        currency: 'IDR',
        status: 'confirmed',
        purchase_order_id: poId,
        items: [
            {
                account_id: accountId,
                description: `E2E P2P ${Date.now()}`,
                quantity: PO_QTY,
                unit_price: UNIT_PRICE,
                discount_percent: 0,
                tax_percent: 0,
            },
        ],
    });
    expect(bill.status).toBeLessThan(400);
    const billId = Number((bill.json.data as { id: number }).id);
    const billAfterCreate = await readBill(page, billId);

    const payment = await send(page, 'POST', '/api/ap-payments', {
        supplier_id: supplierId,
        branch_id: branchId,
        fiscal_year_id: fiscalYearId,
        payment_date: today,
        payment_method: 'bank_transfer',
        bank_account_id: accountId,
        currency: 'IDR',
        total_amount: billTotal,
        status: 'confirmed',
        allocations: [
            { supplier_bill_id: billId, allocated_amount: billTotal },
        ],
    });
    expect(payment.status).toBeLessThan(400);

    const billPaid = await readBill(page, billId);
    const reportsAfter = await readReportTotals(page, fiscalYearId);
    const gapAfter =
        reportsAfter.assets - (reportsAfter.liabilities + reportsAfter.equity);

    return {
        poId,
        poReceivedQty: toNumber(receivedLine.quantity_received),
        stockDelta: stockAfter - stockBefore,
        billId,
        billAmountDueAfterCreate: billAfterCreate.amount_due,
        billStatusAfterPayment: billPaid.status,
        billAmountDueAfterPayment: billPaid.amount_due,
        trialBalanceGap: reportsAfter.trialDebit - reportsAfter.trialCredit,
        balanceSheetGapDelta: gapAfter - gapBefore,
    };
}

test.describe('Procure-to-pay: PR sampai pembayaran supplier', () => {
    test.setTimeout(300000);

    test('PR sampai pembayaran: qty PO, stok, bill, dan jurnal sesuai nilai dokumen', async ({
        page,
    }) => {
        const run = await runProcureToPay(page);

        expect(run.poReceivedQty).toBe(PO_QTY);
        expect(run.stockDelta).toBeCloseTo(PO_QTY, 2);
        expect(run.billAmountDueAfterCreate).toBeCloseTo(
            PO_QTY * UNIT_PRICE,
            2,
        );
        expect(run.billAmountDueAfterPayment).toBeCloseTo(0, 2);
        expect(run.billStatusAfterPayment).toBe('paid');
        expect(run.trialBalanceGap).toBeCloseTo(0, 2);
        expect(run.balanceSheetGapDelta).toBeCloseTo(0, 2);
    });
});
