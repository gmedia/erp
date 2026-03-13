import { test, expect, Page } from '@playwright/test';
import { login } from '../helpers';
import { createAsset, searchAsset } from '../assets/helpers';

let pipelineId: number | null = null;
let assetName: string | null = null;

async function setupPipelineViaApi(page: Page) {
  const apiToken = await page.evaluate(() => localStorage.getItem('api_token'));
  const assetEntityType = String.raw`App\Models\Asset`;
  const headers = { 
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'Authorization': `Bearer ${apiToken}`
  };

  // 0. Clean up existing pipelines for Asset to avoid shadowing
  const pipelinesRes = await page.request.get('/api/pipelines', { headers });
  if (pipelinesRes.ok()) {
      const existingPipelines = (await pipelinesRes.json()).data;
      for (const p of existingPipelines) {
        if (p.entity_type === assetEntityType) {
              await page.request.delete(`/api/pipelines/${p.id}`, { headers });
          }
      }
  }

  // 1. Create Pipeline
  const pipelineRes = await page.request.post('/api/pipelines', {
    headers,
    data: {
      name: `E2E Asset Pipeline ${Date.now()}`,
      code: `e2e_asset_pipeline_${Date.now()}`,
      entity_type: assetEntityType,
      description: 'Pipeline for E2E testing',
      version: 1,
      is_active: true
    }
  });
  expect(pipelineRes.ok()).toBeTruthy();
  const pipeline = (await pipelineRes.json()).data;
  pipelineId = pipeline.id;

  // 2. Create Initial State
  const draftRes = await page.request.post(`/api/pipelines/${pipelineId}/states`, {
    headers,
    data: {
      code: `draft_${Date.now()}`,
      name: 'Draft',
      type: 'initial',
      sort_order: 10,
    }
  });
  expect(draftRes.ok()).toBeTruthy();
  const stateDraft = (await draftRes.json()).data;

  // 3. Create Intermediate State
  const reviewRes = await page.request.post(`/api/pipelines/${pipelineId}/states`, {
    headers,
    data: {
      code: `review_${Date.now()}`,
      name: 'Review',
      type: 'intermediate',
      sort_order: 20,
    }
  });
  expect(reviewRes.ok()).toBeTruthy();
  const stateReview = (await reviewRes.json()).data;

  // 4. Create Transition Draft -> Review (Submit)
  const submitRes = await page.request.post(`/api/pipelines/${pipelineId}/transitions`, {
    headers,
    data: {
      name: 'Submit for Review',
      code: `submit_review_${Date.now()}`,
      from_state_id: stateDraft.id,
      to_state_id: stateReview.id,
      requires_comment: false,
      requires_confirmation: true,
      is_active: true,
      sort_order: 10,
    }
  });
  expect(submitRes.ok()).toBeTruthy();
}

test.describe('Entity State Actions', () => {
    test.beforeAll(async ({ browser }) => {
        // We need an authenticated context for the API calls
        const context = await browser.newContext();
        const page = await context.newPage();
        await login(page);
        await setupPipelineViaApi(page);
        
        // Create an asset to test with
        assetName = await createAsset(page);
        await context.close();
    });

    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('can view and execute transitions from asset profile', async ({ page }) => {
        // 1. Navigate to Asset list and view the created asset
        await page.goto('/assets');
        await searchAsset(page, assetName!);
        
        const row = page.locator('tr').filter({ hasText: assetName! }).first();
        await row.getByRole('button').last().click(); // Open actions dropdown
        await page.getByRole('menuitem', { name: 'View' }).click();

        // 2. We should be on the Profile page, wait for the state to load
        // Initial state should be "Draft"
        await expect(page.getByText('Draft', { exact: true }).first()).toBeVisible();

        // 3. Verify available transition is visible
        const submitBtn = page.getByRole('button', { name: 'Submit for Review' });
        await expect(submitBtn).toBeVisible();

        // 4. Execute transition
        await submitBtn.click();
        
        // It requires confirmation, so dialogue should appear
        const confirmDialog = page.getByRole('alertdialog', { name: 'Confirm Action' });
        await expect(confirmDialog).toBeVisible();
        await confirmDialog.getByRole('button', { name: 'Confirm' }).click();

        // 5. Verify success and state update
        await expect(page.getByText('Transition executed successfully')).toBeVisible();
        await expect(page.getByText('Review', { exact: true }).first()).toBeVisible();
        
        // Transition button should be gone since there are no transitions from 'Review'
        await expect(submitBtn).not.toBeVisible();

        // 6. Navigate to Timeline tab and verify logs
        const timelineResponsePromise = page.waitForResponse(r => r.url().includes('/timeline') && r.status() < 400);
        await page.getByRole('tab', { name: 'Timeline' }).click();
        await timelineResponsePromise;

        const timelinePanel = page.getByRole('tabpanel', { name: 'Timeline' });
        
        // Check if logs are visible. 
        // 1. Initial Assignment log
        await expect(timelinePanel.getByText('"Initial pipeline assignment"')).toBeVisible();
        await expect(timelinePanel.getByText('Assigned Initial State')).toBeVisible();
        
        // 2. Timeline should include the resulting state entry.
        await expect(timelinePanel.getByText('Review').first()).toBeVisible();
    });
});
