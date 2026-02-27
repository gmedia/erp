import { test, expect, Page } from '@playwright/test';
import { login } from '../helpers';
import { searchAsset } from '../assets/helpers';

/**
 * E2E Tests for Asset Pipeline Lifecycle.
 *
 * Uses seeded data from AssetSampleDataSeeder — each asset is in a specific
 * pipeline state: draft, active, maintenance, disposed, lost, cancelled.
 *
 * No API setup required — tests read the existing seeded state.
 */

/** Navigate to asset profile page by searching and clicking View */
async function goToAssetProfile(page: Page, assetCode: string) {
  await page.goto('/assets');
  await searchAsset(page, assetCode);

  const row = page.locator('tr').filter({ hasText: assetCode }).first();
  await expect(row).toBeVisible({ timeout: 10000 });

  // Open actions dropdown → View
  await row.getByRole('button').last().click();
  await page.getByRole('menuitem', { name: 'View' }).click();

  // Wait for profile page to load
  await page.waitForResponse(
    r => r.url().includes('/api/entity-states/') && r.status() < 400
  ).catch(() => null);
}

test.describe('Asset Pipeline Lifecycle — Per State', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  // ── DRAFT STATE ──────────────────────────────────────────────────

  test('draft asset shows Activate and Cancel buttons', async ({ page }) => {
    await goToAssetProfile(page, 'FA-000004');

    // Badge should show "Draft"
    await expect(page.getByText('Draft', { exact: true }).first()).toBeVisible();

    // Available transitions from Draft: Activate, Cancel
    await expect(page.getByRole('button', { name: 'Activate' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Cancel' })).toBeVisible();

    // Should NOT show transitions for other states
    await expect(page.getByRole('button', { name: 'Send to Maintenance' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Dispose' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Mark as Lost' })).not.toBeVisible();
  });

  // ── ACTIVE STATE ─────────────────────────────────────────────────

  test('active asset shows Maintenance, Dispose, Lost buttons', async ({ page }) => {
    await goToAssetProfile(page, 'FA-000001');

    // Badge should show "Active"
    await expect(page.getByText('Active', { exact: true }).first()).toBeVisible();

    // Available transitions from Active: Send to Maintenance, Dispose, Mark as Lost
    await expect(page.getByRole('button', { name: 'Send to Maintenance' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Dispose' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Mark as Lost' })).toBeVisible();

    // Should NOT show transitions from other states
    await expect(page.getByRole('button', { name: 'Activate' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Cancel' })).not.toBeVisible();
  });

  // ── MAINTENANCE STATE ────────────────────────────────────────────

  test('maintenance asset shows Return from Maintenance button', async ({ page }) => {
    await goToAssetProfile(page, 'FA-000006');

    // Badge should show "In Maintenance"
    await expect(page.getByText('In Maintenance', { exact: true }).first()).toBeVisible();

    // Available transition: Return from Maintenance
    await expect(page.getByRole('button', { name: 'Return from Maintenance' })).toBeVisible();

    // Should NOT show other transitions
    await expect(page.getByRole('button', { name: 'Activate' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Dispose' })).not.toBeVisible();
  });

  // ── DISPOSED STATE (final) ───────────────────────────────────────

  test('disposed asset shows no action buttons', async ({ page }) => {
    await goToAssetProfile(page, 'FA-000007');

    // Badge should show "Disposed"
    await expect(page.getByText('Disposed', { exact: true }).first()).toBeVisible();

    // Final state — no transitions available
    await expect(page.getByRole('button', { name: 'Activate' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Send to Maintenance' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Return from Maintenance' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Dispose' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Mark as Lost' })).not.toBeVisible();
  });

  // ── LOST STATE (final) ───────────────────────────────────────────

  test('lost asset shows no action buttons', async ({ page }) => {
    await goToAssetProfile(page, 'FA-000008');

    // Badge should show "Lost"
    await expect(page.getByText('Lost', { exact: true }).first()).toBeVisible();

    // Final state — no transitions
    await expect(page.getByRole('button', { name: 'Activate' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Return from Maintenance' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Mark as Lost' })).not.toBeVisible();
  });

  // ── CANCELLED STATE (final) ──────────────────────────────────────

  test('cancelled asset shows no action buttons', async ({ page }) => {
    await goToAssetProfile(page, 'FA-000009');

    // Badge should show "Cancelled"
    await expect(page.getByText('Cancelled', { exact: true }).first()).toBeVisible();

    // Final state — no transitions
    await expect(page.getByRole('button', { name: 'Activate' })).not.toBeVisible();
    await expect(page.getByRole('button', { name: 'Cancel' })).not.toBeVisible();
  });

  // ── TIMELINE ─────────────────────────────────────────────────────

  test('disposed asset timeline shows complete lifecycle history', async ({ page }) => {
    await goToAssetProfile(page, 'FA-000007');

    // Navigate to Timeline tab
    await page.getByRole('tab', { name: 'Timeline' }).click();

    // Wait for timeline data to load
    await page.waitForResponse(
      r => r.url().includes('/api/entity-states/') && r.url().includes('/timeline') && r.status() < 400
    ).catch(() => null);

    // Should show state history entries
    // FA-000007 lifecycle: Draft → Active → Disposed
    await expect(page.getByText('Initial pipeline assignment').first()).toBeVisible();
    await expect(page.getByText('Activate').first()).toBeVisible();
    await expect(page.getByText('Dispose').first()).toBeVisible();

    // The dispose comment should be visible
    await expect(
      page.getByText('Laptop sudah melewati masa manfaat 3 tahun').first()
    ).toBeVisible();
  });
});
