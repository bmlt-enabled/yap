import { test, expect } from './fixtures/auth.js';

test.describe('Users Management', () => {
  test('can view users page as admin', async ({ adminPage: page }) => {
    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');

    await expect(page.getByRole('button', { name: /add user/i })).toBeVisible();
  });

  test('can add a new user', async ({ adminPage: page }) => {
    const username = `testuser_${Date.now()}`;
    const name = 'Test User';
    const password = 'testpass123';

    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');

    await page.getByRole('button', { name: /add user/i }).click();

    // Wait for dialog to open
    await expect(page.getByRole('dialog')).toBeVisible();

    // MUI TextFields use labels, not IDs
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Display Name').fill(name);
    await page.getByLabel('Password').fill(password);

    await page.getByRole('button', { name: /save/i }).click();

    // Wait for dialog to close and verify user appears in table
    await expect(page.getByRole('dialog')).not.toBeVisible({ timeout: 10000 });
    await expect(page.getByRole('cell', { name: username })).toBeVisible();
  });

  test('can edit an existing user', async ({ adminPage: page }) => {
    // First create a user to edit
    const username = `edituser_${Date.now()}`;
    const name = 'Edit Test User';
    const password = 'testpass123';

    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');

    // Create the user first
    await page.getByRole('button', { name: /add user/i }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Display Name').fill(name);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: /save/i }).click();
    await expect(page.getByRole('dialog')).not.toBeVisible({ timeout: 10000 });

    // Now edit the user - find the row with our username and click its edit button
    const userRow = page.getByRole('row').filter({ hasText: username });
    await userRow.getByRole('button', { name: /edit/i }).click();

    // Wait for dialog to open
    await expect(page.getByRole('dialog')).toBeVisible();

    // Update the name
    await page.getByLabel('Display Name').fill('Updated Name');
    await page.getByRole('button', { name: /save/i }).click();

    // Verify update
    await expect(page.getByRole('dialog')).not.toBeVisible({ timeout: 10000 });
    await expect(page.getByRole('cell', { name: 'Updated Name' })).toBeVisible();
  });

  test('can delete a user', async ({ adminPage: page }) => {
    // First create a user to delete
    const username = `deleteuser_${Date.now()}`;
    const name = 'Delete Test User';
    const password = 'testpass123';

    await page.getByRole('link', { name: 'Users' }).click();
    await page.waitForURL('**/users');

    // Create the user first
    await page.getByRole('button', { name: /add user/i }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Display Name').fill(name);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: /save/i }).click();
    await expect(page.getByRole('dialog')).not.toBeVisible({ timeout: 10000 });

    // Verify user was created
    await expect(page.getByRole('cell', { name: username })).toBeVisible();

    // Find the row with our username and click its delete button
    const userRow = page.getByRole('row').filter({ hasText: username });

    // Handle the confirm dialog (window.confirm)
    page.on('dialog', dialog => dialog.accept());
    await userRow.getByRole('button', { name: /delete/i }).click();

    // Verify user is removed
    await expect(page.getByRole('cell', { name: username })).not.toBeVisible({ timeout: 10000 });
  });
});
