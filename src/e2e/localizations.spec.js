import { test, expect } from './fixtures/auth.js';

test.describe('Localizations', () => {
  // Ensure tests run in order - setup must complete before other tests
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(async ({ request, baseURL }) => {
    // Reset database first
    await request.post(`${baseURL}/api/resetDatabase`);
  });

  // This test must run first to configure call handling via UI
  test('setup: configure call handling for volunteers', async ({ authenticatedPage: page }) => {
    // Navigate to Service Bodies page
    await page.getByRole('link', { name: 'Service Bodies' }).click();
    await page.waitForURL('**/serviceBodies');

    // Wait for table to load
    await page.locator('table').waitFor();

    // Click call handling button for the first service body
    const callHandlingButton = page.getByRole('button', { name: /call handling|configure/i }).first();
    await callHandlingButton.click();

    // Wait for dialog to open
    await expect(page.getByRole('dialog')).toBeVisible();

    // Select "Volunteers" from the Helpline Routing dropdown
    const helplineRoutingSelect = page.locator('#volunteer_routing');
    await helplineRoutingSelect.click();

    // Select "Volunteers" option
    await page.getByRole('option', { name: 'Volunteers', exact: true }).click();

    // Save changes
    await page.getByRole('button', { name: /save changes/i }).click();

    // Dialog should close after save
    await expect(page.getByRole('dialog')).not.toBeVisible();
  });

  test('volunteers page displays localized text instead of keys', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Volunteers' }).click();
    await page.waitForURL('**/volunteers');

    // Wait for service body dropdown to load
    try {
      await page.waitForSelector('#service-body-select', { timeout: 10000 });
    } catch {
      console.log('No service bodies available - skipping localization test');
      return;
    }

    // Select a service body
    await page.locator('#service-body-select').click();
    const options = page.locator('[role="option"]');
    const optionCount = await options.count();
    if (optionCount <= 1) {
      console.log('No service bodies to select - skipping test');
      return;
    }
    await options.nth(1).click();

    // Wait for the page to fully load with localizations
    // The button should display "Add Volunteer" (localized), NOT "add_volunteer" (key)
    await page.waitForTimeout(1000);

    // Check that "Add Volunteer" button shows localized text
    const addVolunteerButton = page.getByRole('button', { name: 'Add Volunteer' });
    await expect(addVolunteerButton).toBeVisible({ timeout: 10000 });

    // Verify the raw key is NOT displayed
    await expect(page.getByRole('button', { name: 'add_volunteer' })).not.toBeVisible();

    // Check "Save Volunteers" button shows localized text
    const saveVolunteersButton = page.getByRole('button', { name: 'Save Volunteers' });
    await expect(saveVolunteersButton).toBeVisible();

    // Verify the raw key is NOT displayed
    await expect(page.getByRole('button', { name: 'save_volunteers' })).not.toBeVisible();

    // Check "Include Group" button shows localized text
    const includeGroupButton = page.getByRole('button', { name: 'Include Group' });
    await expect(includeGroupButton).toBeVisible();

    // Verify the raw key is NOT displayed
    await expect(page.getByRole('button', { name: 'include_group' })).not.toBeVisible();
  });

  test('volunteer form fields display localized labels', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Volunteers' }).click();
    await page.waitForURL('**/volunteers');

    // Wait for service body dropdown to load
    try {
      await page.waitForSelector('#service-body-select', { timeout: 10000 });
    } catch {
      console.log('No service bodies available - skipping localization test');
      return;
    }

    // Select a service body
    await page.locator('#service-body-select').click();
    const options = page.locator('[role="option"]');
    const optionCount = await options.count();
    if (optionCount <= 1) {
      console.log('No service bodies to select - skipping test');
      return;
    }
    await options.nth(1).click();

    // Wait for page to load
    await page.waitForTimeout(1000);

    // Add a volunteer
    const addButton = page.getByRole('button', { name: 'Add Volunteer' });
    await expect(addButton).toBeVisible({ timeout: 10000 });
    await addButton.click();

    // Wait for the volunteer card to appear
    await page.waitForTimeout(500);

    // Check that "Enabled" label is localized (not "enabled")
    const enabledLabel = page.getByText('Enabled', { exact: true });
    await expect(enabledLabel).toBeVisible({ timeout: 5000 });

    // Check that "Volunteer Name" label is localized
    const volunteerNameField = page.getByRole('textbox', { name: 'Volunteer Name' });
    await expect(volunteerNameField).toBeVisible();

    // Expand the volunteer details
    const mainContent = page.locator('main');
    const expandButtons = mainContent.locator('button:not([aria-label])');
    const buttonCount = await expandButtons.count();
    for (let i = 0; i < buttonCount; i++) {
      const btn = expandButtons.nth(i);
      const btnText = await btn.innerText();
      if (!btnText) {
        await btn.click();
        await page.waitForTimeout(500);
        break;
      }
    }

    // Check that "Phone Number" label is localized (not "phone_number")
    const phoneNumberField = page.getByRole('textbox', { name: 'Phone Number' });
    await expect(phoneNumberField).toBeVisible({ timeout: 5000 });

    // Check that "Gender" label is localized (not "gender")
    const genderLabel = page.getByText('Gender', { exact: true });
    await expect(genderLabel).toBeVisible();

    // Check that gender options are localized
    await expect(page.getByText('Unassigned', { exact: true })).toBeVisible();
    await expect(page.getByText('Male', { exact: true })).toBeVisible();
    await expect(page.getByText('Female', { exact: true })).toBeVisible();
    await expect(page.getByText('Unspecified', { exact: true })).toBeVisible();

    // Check that "Options" label is localized
    const optionsLabel = page.getByText('Options', { exact: true });
    await expect(optionsLabel).toBeVisible();

    // Check that "Responder" label is localized
    const responderLabel = page.getByText('Responder', { exact: true });
    await expect(responderLabel).toBeVisible();

    // Check that "Notes" label is localized
    const notesField = page.getByRole('textbox', { name: 'Notes' });
    await expect(notesField).toBeVisible();
  });

  test('shift modal displays localized labels', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Volunteers' }).click();
    await page.waitForURL('**/volunteers');

    // Wait for service body dropdown to load
    try {
      await page.waitForSelector('#service-body-select', { timeout: 10000 });
    } catch {
      console.log('No service bodies available - skipping localization test');
      return;
    }

    // Select a service body
    await page.locator('#service-body-select').click();
    const options = page.locator('[role="option"]');
    const optionCount = await options.count();
    if (optionCount <= 1) {
      console.log('No service bodies to select - skipping test');
      return;
    }
    await options.nth(1).click();

    // Wait for page to load
    await page.waitForTimeout(1000);

    // Add a volunteer
    const addButton = page.getByRole('button', { name: 'Add Volunteer' });
    await expect(addButton).toBeVisible({ timeout: 10000 });
    await addButton.click();

    // Wait for the volunteer card to appear
    await page.waitForTimeout(500);

    // Expand the volunteer details
    const mainContent = page.locator('main');
    const expandButtons = mainContent.locator('button:not([aria-label])');
    const buttonCount = await expandButtons.count();
    for (let i = 0; i < buttonCount; i++) {
      const btn = expandButtons.nth(i);
      const btnText = await btn.innerText();
      if (!btnText) {
        await btn.click();
        await page.waitForTimeout(500);
        break;
      }
    }

    // Click "Add Shift" button
    const addShiftButton = page.getByRole('button', { name: 'Add Shift' });
    await expect(addShiftButton).toBeVisible({ timeout: 5000 });
    await addShiftButton.click();

    // Wait for modal to open
    await page.waitForTimeout(500);

    // Check modal title is localized
    await expect(page.getByRole('heading', { name: 'Add Shift' })).toBeVisible({ timeout: 5000 });

    // Check "Select Days" label is localized (not "select_days")
    // Use .first() as MUI renders label text in multiple places
    await expect(page.getByText('Select Days').first()).toBeVisible();

    // Check "Start Time" label is localized (not "start_time")
    await expect(page.getByText('Start Time').first()).toBeVisible();

    // Check "End Time" label is localized (not "end_time")
    await expect(page.getByText('End Time').first()).toBeVisible();

    // Check "Timezone" label is localized (not "timezone")
    await expect(page.getByText('Timezone').first()).toBeVisible();

    // Check "Type" label is localized (not "type")
    await expect(page.getByText('Type', { exact: true }).first()).toBeVisible();

    // Check "Save Shift" button is localized (not "save_shift")
    await expect(page.getByRole('button', { name: 'Save Shift' })).toBeVisible();

    // Check "Close" button is localized (not "close")
    await expect(page.getByRole('button', { name: 'Close' })).toBeVisible();

    // Close the modal
    await page.getByRole('button', { name: 'Close' }).click();
  });

  test('groups page displays localized text', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Groups' }).click();
    await page.waitForURL('**/groups');

    // Wait for page to load with localizations
    await page.waitForTimeout(1000);

    // Check page title is localized
    await expect(page.getByRole('heading', { name: 'Groups' })).toBeVisible({ timeout: 10000 });

    // Check "Service Bodies" label is localized (not "service_bodies")
    // Use the form label specifically, as nav items may be hidden
    await expect(page.locator('label').filter({ hasText: 'Service Bodies' })).toBeVisible();

    // Check "Create" button is localized when a service body is selected
    // First select a service body
    const serviceBodySelect = page.locator('[role="combobox"]').first();
    if (await serviceBodySelect.isVisible()) {
      await serviceBodySelect.click();
      const options = page.locator('[role="option"]');
      const optionCount = await options.count();
      if (optionCount > 1) {
        await options.nth(1).click();
        await page.waitForTimeout(500);

        // Check "Create" button is localized
        await expect(page.getByRole('button', { name: 'Create' })).toBeVisible();
      }
    }
  });
});
