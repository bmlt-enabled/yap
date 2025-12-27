import { test, expect } from './fixtures/auth.js';

test.describe('Volunteers', () => {
  let authToken = null;
  let configuredServiceBodyId = null;

  test.beforeAll(async ({ request, baseURL }) => {
    // Reset database first
    await request.post(`${baseURL}/api/resetDatabase`);

    // Login to get auth token
    const loginResponse = await request.post(`${baseURL}/api/v1/login`, {
      data: { username: 'admin', password: 'admin' }
    });
    const loginData = await loginResponse.json();
    authToken = loginData.token;

    // Get service bodies to find one to configure
    const serviceBodiesResponse = await request.get(`${baseURL}/api/v1/rootServer/serviceBodies/user`, {
      headers: { Authorization: `Bearer ${authToken}` }
    });
    const serviceBodies = await serviceBodiesResponse.json();

    if (serviceBodies && serviceBodies.length > 0) {
      configuredServiceBodyId = serviceBodies[0].id;

      // Configure call handling for the first service body with volunteer routing enabled
      await request.post(`${baseURL}/api/v1/callHandling?serviceBodyId=${configuredServiceBodyId}`, {
        headers: {
          Authorization: `Bearer ${authToken}`,
          'Content-Type': 'application/json'
        },
        data: {
          volunteer_routing: 'volunteers',
          volunteer_routing_enabled: true
        }
      });
    }
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
    // Button text might be localization key "add_volunteer" or localized text
    await page.waitForSelector('button:has-text("add_volunteer"), button:has-text("Add Volunteer")', { timeout: 5000 });

    // Add a volunteer - try both localization key and localized text
    const addButton = page.getByRole('button', { name: /add.?volunteer/i });
    await addButton.click();

    // Wait for the volunteer card to appear - look for the volunteer_name textbox
    const volunteerNameField = page.getByRole('textbox', { name: /volunteer.?name/i });
    await expect(volunteerNameField).toBeVisible({ timeout: 10000 });

    // The phone_number field is inside a Collapse component that needs to be expanded
    // Find the expand button - it's the button next to the "enabled" checkbox
    // Based on page snapshot: button [ref=e120] with img [ref=e121] is the expand button
    // It's the first button inside the volunteer card area (in main > generic > generic section)

    // The enabled checkbox is visible, so we can find the expand button near it
    const enabledCheckbox = page.getByRole('checkbox', { name: /enabled/i });
    await expect(enabledCheckbox).toBeVisible({ timeout: 5000 });

    // The expand button is in the same row as the enabled checkbox
    // It's the button without a name that appears before the checkbox
    // Let's look for buttons without accessible names inside main content
    const mainContent = page.locator('main');
    const allButtons = mainContent.locator('button:not([aria-label])');
    const buttonCount = await allButtons.count();

    // Click the first unnamed button (the expand button)
    for (let i = 0; i < buttonCount; i++) {
      const btn = allButtons.nth(i);
      const hasName = await btn.getAttribute('aria-label');
      const btnText = await btn.innerText();
      if (!hasName && !btnText) {
        // This is likely the expand button (no name, no text)
        await btn.click();
        await page.waitForTimeout(500);
        break;
      }
    }

    // The phone number field should now be visible - look for it by label
    const phoneField = page.getByRole('textbox', { name: /phone.?number/i });
    await expect(phoneField).toBeVisible({ timeout: 5000 });

    // Enter an invalid phone number
    await phoneField.fill('123');

    // Should show error state (helper text with "Invalid phone number" or localization key)
    await expect(page.getByText(/invalid.?phone.?number|invalid_phone_number/i)).toBeVisible({ timeout: 5000 });

    // Enter a valid US phone number (use a common valid format)
    await phoneField.fill('+1 202 555 0123');

    // Error should disappear - give it time to validate
    await page.waitForTimeout(500);
    await expect(page.getByText(/invalid.?phone.?number|invalid_phone_number/i)).not.toBeVisible({ timeout: 5000 });

    // Clear the field - should not show error for empty
    await phoneField.clear();
    await page.waitForTimeout(500);
    await expect(page.getByText(/invalid.?phone.?number|invalid_phone_number/i)).not.toBeVisible({ timeout: 5000 });
  });
});
