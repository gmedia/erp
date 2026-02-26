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
      name: `E2E Timeline Pipeline ${Date.now()}`,
      code: `e2e_timeline_pipeline_${Date.now()}`,
      entity_type: 'App\\Models\\Asset',
      description: 'Pipeline for E2E testing timeline module',
      version: 1,
      is_active: true
    }
  });
  if (!pipelineRes.ok()) throw new Error(`Failed to create pipeline: ${await pipelineRes.text()}`);
  const pipeline = (await pipelineRes.json()).data;
  pipelineId = pipeline.id;

  // 2. Create States
  const draftRes = await page.request.post(`/api/pipelines/${pipelineId}/states`, {
    headers,
    data: { code: `draft_${Date.now()}`, name: 'Draft', type: 'initial', sort_order: 10 }
  });
  if (!draftRes.ok()) throw new Error(`Failed to create draft state: ${await draftRes.text()}`);
  const draft = (await draftRes.json()).data;

  const reviewRes = await page.request.post(`/api/pipelines/${pipelineId}/states`, {
    headers,
    data: { code: `review_${Date.now()}`, name: 'Review', type: 'intermediate', sort_order: 20 }
  });
  if (!reviewRes.ok()) throw new Error(`Failed to create review state: ${await reviewRes.text()}`);
  const review = (await reviewRes.json()).data;

  // 3. Create Transition
  const transRes = await page.request.post(`/api/pipelines/${pipelineId}/transitions`, {
    headers,
    data: {
      name: 'Submit for Review',
      code: `submit_review_${Date.now()}`,
      from_state_id: draft.id,
      to_state_id: review.id,
      requires_comment: true,
      requires_confirmation: false,
      is_active: true,
      sort_order: 10
    }
  });
  if (!transRes.ok()) throw new Error(`Failed to create transition: ${await transRes.text()}`);
}

test.describe('Entity State Timeline', () => {
    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();
        await login(page);
        await setupPipelineViaApi(page);
        await page.close();
    });

    test('can view timeline history after transitions', async ({ page }) => {
        await login(page);

        // 1. Create a new asset to trigger initial pipeline assignment
        assetName = `Timeline Test Asset ${Date.now()}`;
        await createAsset(page, { name: assetName });

        // Navigate to the asset profile
        await page.goto('/assets');
        await searchAsset(page, assetName);
        const row = page.locator('tr').filter({ hasText: assetName }).first();
        await row.getByRole('button').last().click(); // Open actions dropdown
        await page.getByRole('menuitem', { name: 'View' }).click();
        await page.waitForTimeout(1000); // Wait for profile page to load and API to fetch state
        await expect(page.getByText('Draft', { exact: true }).first()).toBeVisible();

        // 2. Verify initial timeline tab has only one entry (assignment)
        await page.getByRole('tab', { name: 'Timeline' }).click();
        await expect(page.getByText('Initial pipeline assignment').first()).toBeVisible();

        // 3. Execute a transition with comment
        await page.getByRole('button', { name: 'Submit for Review' }).click();
        
        // Fill comment dialog
        await page.getByPlaceholder('Enter your comment here...').fill('Ready for QA review');
        await page.getByRole('button', { name: 'Submit' }).click();
        
        // Wait for success toast and badge update
        await expect(page.getByText('Transition executed successfully')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Submit for Review' })).not.toBeVisible();
        await expect(page.locator('.gap-3 > .inline-flex', { hasText: 'Review' })).toBeVisible();

        // 4. Verify Timeline tab has the new entry
        await page.getByRole('tab', { name: 'Timeline' }).click();
        
        // Should have 2 items in timeline now. We check uniqueness by specific texts
        await expect(page.getByText('Ready for QA review')).toBeVisible();
        await expect(page.getByText('Submit for Review').first()).toBeVisible();
        
        // Performer should be recorded
        await expect(page.getByText(/Performed by/i).first()).toBeVisible();
    });
});
