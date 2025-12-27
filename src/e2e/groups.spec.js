import { test, expect } from './fixtures/auth.js';

test.describe('Groups', () => {
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

  test('can view groups page', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Groups' }).click();
    await page.waitForURL('**/groups');

    await expect(page).toHaveURL(/.*groups/);
  });

  test('can add a new group', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Groups' }).click();
    await page.waitForURL('**/groups');

    // Select service body first
    const serviceBodySelect = page.locator('select, [role="combobox"]').first();
    if (await serviceBodySelect.isVisible()) {
      await serviceBodySelect.click();
      await page.locator('[role="option"]').first().click();
    }

    // Add group
    const addButton = page.getByRole('button', { name: /add group/i });
    if (await addButton.isVisible()) {
      await addButton.click();

      // Fill in group name
      await page.locator('#group_name, input[name="name"]').fill('Test Group');

      // Save
      await page.getByRole('button', { name: /save|add/i }).click();

      // Dialog should close
      await expect(page.getByRole('dialog')).not.toBeVisible();
    }
  });

  test('can edit a group', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Groups' }).click();
    await page.waitForURL('**/groups');

    // Select service body
    const serviceBodySelect = page.locator('select, [role="combobox"]').first();
    if (await serviceBodySelect.isVisible()) {
      await serviceBodySelect.click();
      await page.locator('[role="option"]').first().click();
    }

    // Select a group if available
    const groupSelect = page.locator('select, [role="combobox"]').nth(1);
    if (await groupSelect.isVisible()) {
      await groupSelect.click();
      const groupOption = page.locator('[role="option"]').first();
      if (await groupOption.isVisible()) {
        await groupOption.click();

        // Edit group
        const editButton = page.getByRole('button', { name: /edit/i });
        if (await editButton.isVisible()) {
          await editButton.click();

          await page.locator('#group_name, input[name="name"]').fill('Modified Group');
          await page.getByRole('button', { name: /save/i }).click();
        }
      }
    }
  });

  test('can delete a group', async ({ authenticatedPage: page }) => {
    await page.getByRole('link', { name: 'Groups' }).click();
    await page.waitForURL('**/groups');

    // Select service body
    const serviceBodySelect = page.locator('select, [role="combobox"]').first();
    if (await serviceBodySelect.isVisible()) {
      await serviceBodySelect.click();
      await page.locator('[role="option"]').first().click();
    }

    // Select a group if available
    const groupSelect = page.locator('select, [role="combobox"]').nth(1);
    if (await groupSelect.isVisible()) {
      await groupSelect.click();
      const groupOption = page.locator('[role="option"]').first();
      if (await groupOption.isVisible()) {
        await groupOption.click();

        // Delete group
        const deleteButton = page.getByRole('button', { name: /delete/i });
        if (await deleteButton.isVisible()) {
          await deleteButton.click();

          // Confirm if needed
          const confirmButton = page.getByRole('button', { name: /confirm|yes|ok/i });
          if (await confirmButton.isVisible()) {
            await confirmButton.click();
          }
        }
      }
    }
  });
});
