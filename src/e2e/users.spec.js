import { test, expect } from './fixtures/auth.js';

async function waitForUserDialogReady(page) {
  const dialog = page.getByRole('dialog');
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole('progressbar')).toHaveCount(0, { timeout: 15000 });
}

async function saveUserDialog(page) {
  const dialog = page.getByRole('dialog');
  const saveResponse = page.waitForResponse(
    (response) =>
      response.url().includes('/api/v1/users') &&
      ['POST', 'PUT'].includes(response.request().method()),
    { timeout: 30000 },
  );
  await dialog.getByRole('button', { name: /^save$/i }).click();
  const response = await saveResponse;
  const body = await response.text();
  expect(response.ok(), `User save failed (${response.status()}): ${body}`).toBeTruthy();
  await expect(dialog).not.toBeVisible({ timeout: 15000 });
}

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

    await waitForUserDialogReady(page);

    // MUI TextFields use labels, not IDs
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Display Name').fill(name);
    await page.getByLabel('Password').fill(password);

    await saveUserDialog(page);
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
    await waitForUserDialogReady(page);
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Display Name').fill(name);
    await page.getByLabel('Password').fill(password);
    await saveUserDialog(page);

    // Now edit the user - find the row with our username and click its edit button
    const userRow = page.getByRole('row').filter({ hasText: username });
    await userRow.getByRole('button', { name: /edit/i }).click();

    // Wait for dialog to open
    await waitForUserDialogReady(page);

    // Update the name
    await page.getByLabel('Display Name').fill('Updated Name');
    await saveUserDialog(page);
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
    await waitForUserDialogReady(page);
    await page.getByLabel('Username').fill(username);
    await page.getByLabel('Display Name').fill(name);
    await page.getByLabel('Password').fill(password);
    await saveUserDialog(page);

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
