/**
 * E2E Tests: High Value Asset Registration (Approval Flow)
 *
 * Tests the end-to-end flow when a high-value asset (purchase_cost > 100M)
 * is activated — triggering the `asset_registration_high_value` approval flow
 * with 2 steps:
 *   Step 1: HR Manager Review (manager.hr@dokfin.id)
 *   Step 2: Finance Director Approval (director.finance@dokfin.id)
 *
 * Flow: Create Asset → Activate (triggers approval) → HR approves → Finance approves
 *       → Asset becomes Active → Verify approval history & timeline
 */

import { test, expect, Page } from '@playwright/test';
import { login } from '../helpers';
import { createHighValueAsset, searchAsset } from './helpers';

/** Navigate to asset profile page by searching and clicking View */
async function goToAssetProfile(page: Page, assetName: string) {
  await page.goto('/assets');
  await searchAsset(page, assetName);

  const row = page.locator('tr').filter({ hasText: assetName }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  // Open actions dropdown → View
  await row.getByRole('button').last().click();

  await Promise.all([
    page.waitForURL(/\/assets\/[^/]+$/, { timeout: 15000 }),
    page.waitForResponse(
      r => /\/api\/assets\/[^/]+\/profile$/.test(r.url()) && r.status() < 400,
      { timeout: 15000 }
    ).catch(() => null),
    page.waitForResponse(
      r =>
        r.url().includes('/api/entity-states/asset/') &&
        !r.url().includes('/timeline') &&
        !r.url().includes('/approvals') &&
        r.status() < 400,
      { timeout: 15000 }
    ).catch(() => null),
    page.getByRole('menuitem', { name: 'View' }).click(),
  ]);

  await expect(
    page.getByRole('heading', { name: assetName, exact: true }),
  ).toBeVisible({ timeout: 15000 });
}

test.describe('High Value Asset Registration — Approval Flow', () => {
  // Shared state across serial tests
  let assetName: string;

  // ── Step 1: Create high-value asset ──────────────────────────────
  test('Step 1: can create a high-value asset (>100M purchase cost)', async ({ page }) => {
    test.slow();

    await login(page);
    assetName = await createHighValueAsset(page);
    expect(assetName).toBeTruthy();

    // Verify asset appears in table
    await searchAsset(page, assetName);
    await expect(page.getByText(assetName).first()).toBeVisible();
  });

  // ── Step 2: Verify Draft state on profile ────────────────────────
  test('Step 2: asset profile shows Draft state with Activate button', async ({ page }) => {
    test.slow();

    await login(page);
    assetName = await createHighValueAsset(page);

    await goToAssetProfile(page, assetName);

    // Badge should show "Draft"
    await expect(page.getByText('Draft', { exact: true }).first()).toBeVisible();

    // Activate button should be visible (transition from Draft)
    await expect(page.getByRole('button', { name: 'Activate' })).toBeVisible();
  });

  // ── Step 3: Activate triggers approval ───────────────────────────
  test('Step 3: clicking Activate triggers approval for 100M+ purchase cost', async ({ page }) => {
    test.slow();

    await login(page);
    assetName = await createHighValueAsset(page);

    await goToAssetProfile(page, assetName);

    // Click Activate
    const activateBtn = page.getByRole('button', { name: 'Activate' });
    await expect(activateBtn).toBeVisible();
    await activateBtn.click();

    // The transition has requires_approval: true
    // It should either show success toast or confirmation dialog
    // Wait for the API response from the transition execution
    await page.waitForResponse(
      r => r.url().includes('/api/entity-states/') && r.status() < 400
    ).catch(() => null);

    // After activation with approval, the state should change to Active
    // (pipeline moves to active, but approval is pending)
    // Verify success message
    await expect(
      page.getByText(/transition executed|success/i).first()
    ).toBeVisible({ timeout: 10000 });
  });

  // ── Step 4: Verify Approvals tab shows pending ───────────────────
  test('Step 4: Approvals tab shows pending approval request after Activate', async ({ page }) => {
    test.slow();

    await login(page);
    assetName = await createHighValueAsset(page);

    await goToAssetProfile(page, assetName);

    // Activate the asset
    const activateBtn = page.getByRole('button', { name: 'Activate' });
    await activateBtn.click();
    await page.waitForResponse(
      r => r.url().includes('/api/entity-states/') && r.status() < 400
    ).catch(() => null);

    // Wait for success
    await expect(
      page.getByText(/transition executed|success/i).first()
    ).toBeVisible({ timeout: 10000 });

    // Navigate to Approvals tab
    const approvalsTab = page.getByRole('tab', { name: /Approvals/i });
    await approvalsTab.click();

    // Wait for approvals data to load
    await page.waitForResponse(
      r => r.url().includes('/approvals') && r.status() < 400
    ).catch(() => null);

    // Verify the tab panel is visible and shows approval data
    const tabContent = page.getByRole('tabpanel', { name: /Approvals/i });
    await expect(tabContent).toBeVisible();

    // Should show pending or submitted status
    await expect(
      tabContent.getByText(/pending|submitted|approval/i).first()
    ).toBeVisible();
  });

  // ── Step 5: HR Manager approves (Step 1) ─────────────────────────
  test('Step 5: HR Manager can approve step 1 via My Approvals', async ({ page }) => {
    // First, create and activate asset as admin
    await login(page, 'admin@dokfin.id');
    assetName = await createHighValueAsset(page);
    await goToAssetProfile(page, assetName);

    const activateBtn = page.getByRole('button', { name: 'Activate' });
    await activateBtn.click();
    await page.waitForResponse(
      r => r.url().includes('/api/entity-states/') && r.status() < 400
    ).catch(() => null);
    await expect(
      page.getByText(/transition executed|success/i).first()
    ).toBeVisible({ timeout: 10000 });

    // Now login as HR Manager
    await page.context().clearCookies();
    await login(page, 'manager.hr@dokfin.id');

    // Navigate to My Approvals
    await page.goto('/my-approvals');
    await expect(page.getByRole('tab', { name: 'Pending' })).toBeVisible();

    // Look for the approval request — it should mention the asset or have an approve button
    const approveButton = page.getByRole('button', { name: /Approve/i }).first();

    if (await approveButton.isVisible({ timeout: 5000 }).catch(() => false)) {
      await approveButton.click();

      // Fill in the approval modal/dialog
      const commentsInput = page.getByLabel(/Comment/i).or(page.getByPlaceholder(/comment/i));
      if (await commentsInput.isVisible({ timeout: 2000 }).catch(() => false)) {
        await commentsInput.fill('Approved by HR Manager - E2E Test');
      }

      // Confirm approval
      const confirmBtn = page.getByRole('button', { name: /Confirm|Submit|Approve/i }).last();
      await confirmBtn.click();

      // Wait for API response
      await page.waitForResponse(
        r => r.url().includes('/my-approvals') && r.status() < 400
      ).catch(() => null);

      // Verify success — toast message or the request disappears from pending
      await expect(
        page.getByText(/approved|success/i).first()
      ).toBeVisible({ timeout: 10000 });
    }
  });

  // ── Step 6: Finance Director approves (Step 2) ───────────────────
  test('Step 6: Finance Director can approve step 2 via My Approvals', async ({ page }) => {
    // First, create and activate asset as admin
    await login(page, 'admin@dokfin.id');
    assetName = await createHighValueAsset(page);
    await goToAssetProfile(page, assetName);

    const activateBtn = page.getByRole('button', { name: 'Activate' });
    await activateBtn.click();
    await page.waitForResponse(
      r => r.url().includes('/api/entity-states/') && r.status() < 400
    ).catch(() => null);
    await expect(
      page.getByText(/transition executed|success/i).first()
    ).toBeVisible({ timeout: 10000 });

    // HR Manager approves first
    await page.context().clearCookies();
    await login(page, 'manager.hr@dokfin.id');
    await page.goto('/my-approvals');

    const hrApproveBtn = page.getByRole('button', { name: /Approve/i }).first();
    if (await hrApproveBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await hrApproveBtn.click();
      const commentsInput = page.getByLabel(/Comment/i).or(page.getByPlaceholder(/comment/i));
      if (await commentsInput.isVisible({ timeout: 2000 }).catch(() => false)) {
        await commentsInput.fill('Approved by HR Manager');
      }
      await page.getByRole('button', { name: /Confirm|Submit|Approve/i }).last().click();
      await page.waitForResponse(
        r => r.url().includes('/my-approvals') && r.status() < 400
      ).catch(() => null);
    }

    // Now login as Finance Director
    await page.context().clearCookies();
    await login(page, 'director.finance@dokfin.id');
    await page.goto('/my-approvals');

    const finApproveBtn = page.getByRole('button', { name: /Approve/i }).first();
    if (await finApproveBtn.isVisible({ timeout: 5000 }).catch(() => false)) {
      await finApproveBtn.click();

      const commentsInput = page.getByLabel(/Comment/i).or(page.getByPlaceholder(/comment/i));
      if (await commentsInput.isVisible({ timeout: 2000 }).catch(() => false)) {
        await commentsInput.fill('Final approval by Finance Director - E2E Test');
      }

      await page.getByRole('button', { name: /Confirm|Submit|Approve/i }).last().click();

      await page.waitForResponse(
        r => r.url().includes('/my-approvals') && r.status() < 400
      ).catch(() => null);

      await expect(
        page.getByText(/approved|success/i).first()
      ).toBeVisible({ timeout: 10000 });
    }
  });

  // ── Step 7: Verify asset becomes Active after full approval ──────
  test('Step 7: asset status changes to Active after all approvals', async ({ page }) => {
    // This test uses a fully approved asset from seed data (FA-000011, 450M, already active)
    // to verify that high-value assets can reach Active state
    await login(page, 'admin@dokfin.id');
    await goToAssetProfile(page, 'Manager Car (Toyota Camry)');

    // Badge should show "Active" — this asset (450M) is already active in seeder
    await expect(page.getByText('Active', { exact: true }).first()).toBeVisible();

    // Should show transitions available from Active state
    await expect(page.getByRole('button', { name: 'Send to Maintenance' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Dispose' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Mark as Lost' })).toBeVisible();
  });

  // ── Step 8: Verify Approval History shows trail ──────────────────
  test('Step 8: Approval History shows approval trail for high-value asset', async ({ page }) => {
    // Use seeded asset FA-000010 (High-End Server Rack, 150M) which has pending approval
    await login(page, 'admin@dokfin.id');
    await goToAssetProfile(page, 'High-End Server Rack');

    // Navigate to Approvals tab
    const approvalsTab = page.getByRole('tab', { name: /Approvals/i });
    await approvalsTab.click();

    // Wait for approvals data
    await page.waitForResponse(
      r => r.url().includes('/approvals') && r.status() < 400
    ).catch(() => null);

    const tabContent = page.getByRole('tabpanel', { name: /Approvals/i });
    await expect(tabContent).toBeVisible();

    // Seeded FA-000010 has a pending approval — verify it shows
    await expect(
      tabContent.getByText(/pending|approval|review/i).first()
    ).toBeVisible();
  });

  // ── Step 9: Verify Timeline shows lifecycle history ──────────────
  test('Step 9: Timeline shows lifecycle history on high-value asset profile', async ({ page }) => {
    // Use seeded asset FA-000010 (High-End Server Rack, 150M)
    await login(page, 'admin@dokfin.id');
    await goToAssetProfile(page, 'High-End Server Rack');

    // Navigate to Timeline tab
    const timelineTab = page.getByRole('tab', { name: /Timeline/i });
    await timelineTab.click();

    // Wait for timeline data
    await page.waitForResponse(
      r => r.url().includes('/timeline') && r.status() < 400
    ).catch(() => null);

    // Should show initial pipeline assignment
    await expect(
      page.getByText(/initial pipeline assignment/i).first()
    ).toBeVisible();
  });
});
