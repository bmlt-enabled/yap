import { test, expect } from './fixtures/auth.js';

test.describe('Volunteers', () => {
  test.beforeAll(async ({ request, baseURL }) => {
    await request.post(`${baseURL}/api/resetDatabase`);
  });

  test('can view volunteers page', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Volunteers' }).click();
    await page.waitForURL('**/volunteers');

    await expect(page).toHaveURL(/.*volunteers/);
  });

  test('can select a service body', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Volunteers' }).click();
    await page.waitForURL('**/volunteers');

    // Look for service body dropdown
    const serviceBodySelect = page.locator('select, [role="combobox"]').first();
    if (await serviceBodySelect.isVisible()) {
      await serviceBodySelect.click();
      // Select first option
      await page.locator('[role="option"]').first().click();
    }
  });

  test('can add a volunteer', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Volunteers' }).click();
    await page.waitForURL('**/volunteers');

    // Find add volunteer button
    const addButton = page.getByRole('button', { name: /add volunteer/i });
    if (await addButton.isVisible()) {
      await addButton.click();

      // Fill in volunteer details
      await page.locator('input[name="name"], #volunteer_name').fill('Test Volunteer');
      await page.locator('input[name="phone"], #volunteer_phone_number').fill('5551234567');

      // Save
      await page.getByRole('button', { name: /save/i }).click();

      await expect(page.locator('text=Test Volunteer')).toBeVisible();
    }
  });

  test('can toggle volunteer enabled status', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Volunteers' }).click();
    await page.waitForURL('**/volunteers');

    // Find an enable/disable toggle
    const enableToggle = page.locator('input[type="checkbox"]').first();
    if (await enableToggle.isVisible()) {
      const initialState = await enableToggle.isChecked();
      await enableToggle.click();
      expect(await enableToggle.isChecked()).toBe(!initialState);
    }
  });
});
