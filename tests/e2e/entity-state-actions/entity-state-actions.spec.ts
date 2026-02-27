import { test, expect, Page } from '@playwright/test';
import { login } from '../helpers';
import { createAsset, searchAsset } from '../assets/helpers';

let pipelineId: number | null = null;
let assetName: string | null = null;

async function setupPipelineViaApi(page: Page) {
  const cookies = await page.context().cookies();
  const xsrfTokenCookie = cookies.find(c => c.name === 'XSRF-TOKEN');
  const xsrfToken = decodeURIComponent(xsrfTokenCookie?.value || '');
  const headers = { 
    'Accept': 'application/json',
    'X-XSRF-TOKEN': xsrfToken
  };

  // 0. Clean up existing pipelines for Asset to avoid shadowing
  const pipelinesRes = await page.request.get('/api/pipelines', { headers });
  if (pipelinesRes.ok()) {
      const existingPipelines = (await pipelinesRes.json()).data;
      for (const p of existingPipelines) {
          if (p.entity_type === 'App\\Models\\Asset') {
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
      entity_type: 'App\\Models\\Asset',
      description: 'Pipeline for E2E testing',
      version: 1,
      is_active: true
    }
  });
  if (!pipelineRes.ok()) {
      const err = await pipelineRes.json();
      console.log('API Error:', err.message || err);
  }
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
  if (!draftRes.ok()) {
      const err = await draftRes.json();
      console.log('API Error State Draft:', err.message || err);
  }
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
  if (!reviewRes.ok()) {
      const err = await reviewRes.json();
      console.log('API Error State Review:', err.message || err);
  }
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
  if (!submitRes.ok()) {
      const err = await submitRes.json();
      console.log('API Error Transition Sub:', err.message || err);
  }
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
        await page.getByRole('tab', { name: 'Timeline' }).click();
        
        // Check if logs are visible. 
        // 1. Initial Assignment log
        await expect(page.getByText('"Initial pipeline assignment"')).toBeVisible();
        await expect(page.getByText('Assigned Initial State')).toBeVisible();
        
        // 2. Transition log
        await expect(page.locator('span').filter({ hasText: 'Submit for Review' }).first()).toBeVisible();
    });
});
