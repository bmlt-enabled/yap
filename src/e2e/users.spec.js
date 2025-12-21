import { test, expect } from './fixtures/auth.js';

test.describe('Users Management', () => {
  test.beforeAll(async ({ request, baseURL }) => {
    await request.post(`${baseURL}/api/resetDatabase`);
  });

  test('can view users page as admin', async ({ adminPage: page }) => {
    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');

    await expect(page.getByRole('button', { name: /add user/i })).toBeVisible();
  });

  test('can add a new user', async ({ adminPage: page }) => {
    const username = 'testuser';
    const name = 'Test User';
    const password = 'testpass123';

    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');

    await page.getByRole('button', { name: /add user/i }).click();

    await page.locator('#username').fill(username);
    await page.locator('#name').fill(name);
    await page.locator('#password').fill(password);

    await page.getByRole('button', { name: /save/i }).click();

    await expect(page.locator(`text=${username}`)).toBeVisible();
    await expect(page.locator(`text=${name}`)).toBeVisible();
  });

  test('can edit an existing user', async ({ adminPage: page }) => {
    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');

    // Click edit on existing user
    await page.getByRole('button', { name: /edit/i }).first().click();

    // Update the name
    await page.locator('#name').fill('Updated Name');
    await page.getByRole('button', { name: /save/i }).click();

    await expect(page.locator('text=Updated Name')).toBeVisible();
  });

  test('can delete a user', async ({ adminPage: page }) => {
    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');

    // Get initial row count
    const initialRows = await page.locator('tbody tr').count();

    // Click delete on a user
    await page.getByRole('button', { name: /delete/i }).first().click();

    // Confirm deletion if there's a confirmation dialog
    const confirmButton = page.getByRole('button', { name: /confirm|yes|ok/i });
    if (await confirmButton.isVisible()) {
      await confirmButton.click();
    }

    // Verify row count decreased
    await expect(page.locator('tbody tr')).toHaveCount(initialRows - 1);
  });
});
