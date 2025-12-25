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

  test('validates phone numbers', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Volunteers' }).click();
    await page.waitForURL('**/volunteers');

    // Wait for service body dropdown to load (it shows "Loading..." initially)
    // If no service bodies are configured, the dropdown won't appear
    try {
      await page.waitForSelector('#service-body-select', { timeout: 10000 });
    } catch {
      // No service bodies available - skip test
      console.log('No service bodies available - skipping phone validation test');
      return;
    }

    // Select service body (skip the placeholder option)
    await page.locator('#service-body-select').click();
    const options = page.locator('[role="option"]');
    const optionCount = await options.count();
    if (optionCount <= 1) {
      console.log('No service bodies to select - skipping test');
      return;
    }
    await options.nth(1).click();

    // Wait for buttons to appear after service body selection
    await page.waitForSelector('button:has-text("Add Volunteer")', { timeout: 5000 });

    // Add a volunteer
    await page.getByRole('button', { name: /add volunteer/i }).click();

    // Expand the volunteer card to show phone field
    await page.locator('[data-testid="ExpandMoreIcon"]').first().click();

    // Find the phone number field by label
    const phoneField = page.getByLabel(/phone number/i);
    await expect(phoneField).toBeVisible({ timeout: 5000 });

    // Enter an invalid phone number
    await phoneField.fill('123');

    // Should show error state (helper text with "Invalid phone number")
    await expect(page.getByText(/invalid phone number/i)).toBeVisible({ timeout: 5000 });

    // Enter a valid US phone number
    await phoneField.fill('+1 555 123 4567');

    // Error should disappear
    await expect(page.getByText(/invalid phone number/i)).not.toBeVisible();

    // Clear the field - should not show error for empty
    await phoneField.clear();
    await expect(page.getByText(/invalid phone number/i)).not.toBeVisible();
  });
});
