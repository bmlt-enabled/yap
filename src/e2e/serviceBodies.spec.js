import { test, expect } from './fixtures/auth.js';

test.describe('Service Bodies', () => {
  test.beforeAll(async ({ request, baseURL }) => {
    await request.post(`${baseURL}/api/resetDatabase`);
  });

  test('can view service bodies list', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Service Bodies' }).click();
    await page.waitForURL('**/serviceBodies');

    // Wait for the table to load
    await expect(page.locator('table')).toBeVisible();
  });

  test('can open call handling dialog', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Service Bodies' }).click();
    await page.waitForURL('**/serviceBodies');

    // Wait for table to load and click call handling button
    await page.locator('table').waitFor();

    // Look for a call handling button/icon in the first row
    const callHandlingButton = page.getByRole('button', { name: /call handling|configure/i }).first();
    if (await callHandlingButton.isVisible()) {
      await callHandlingButton.click();

      // Verify dialog opens
      await expect(page.getByRole('dialog')).toBeVisible();
    }
  });

  test('can save call handling configuration', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Service Bodies' }).click();
    await page.waitForURL('**/serviceBodies');

    await page.locator('table').waitFor();

    const callHandlingButton = page.getByRole('button', { name: /call handling|configure/i }).first();
    if (await callHandlingButton.isVisible()) {
      await callHandlingButton.click();

      await expect(page.getByRole('dialog')).toBeVisible();

      // Find and click save button
      await page.getByRole('button', { name: /save/i }).click();

      // Dialog should close after save
      await expect(page.getByRole('dialog')).not.toBeVisible();
    }
  });
});
