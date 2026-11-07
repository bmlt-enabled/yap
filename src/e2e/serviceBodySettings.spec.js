import { test, expect, resetDatabase } from './fixtures/auth.js';
import { BROOKLYN } from './fixtures/seed.js';

const SETTING = 'blocklist';
const VALUE = '+15005550666,+15005550667';

async function openConfigure(page) {
  await page.getByRole('link', { name: 'Service Bodies' }).click();
  await page.waitForURL('**/serviceBodies');

  const loaded = page.waitForResponse((r) => r.url().includes(`/api/v1/settings/serviceBody/${BROOKLYN.id}`) && r.ok());
  await page.getByRole('row').filter({ hasText: BROOKLYN.row }).getByRole('button', { name: 'Configure' }).click();
  await loaded;

  const dialog = page.getByRole('dialog');
  await expect(dialog.getByRole('heading', { name: `Configure ${BROOKLYN.name} (${BROOKLYN.id})` })).toBeVisible();
  return dialog;
}

async function save(page, dialog) {
  const saved = page.waitForResponse(
    (r) => r.request().method() === 'POST' && r.url().includes(`/api/v1/settings/serviceBody/${BROOKLYN.id}`),
  );
  await dialog.getByRole('button', { name: 'Save Changes' }).click();
  expect((await saved).ok()).toBeTruthy();
  await expect(dialog).toHaveCount(0);
}

// The dialog shows one labelled text box per override; these tests add one.
async function settingField(dialog, setting) {
  await expect(dialog.getByText(setting, { exact: true })).toBeVisible();
  return dialog.getByPlaceholder('Enter value');
}

test.describe('Per-service-body settings', () => {
  test.beforeAll(() => {
    resetDatabase();
  });

  test('starts with no overrides', async ({ authenticatedPage: page }) => {
    const dialog = await openConfigure(page);
    await expect(dialog.getByPlaceholder('Enter value')).toHaveCount(0);
  });

  test('adds an override and saves it', async ({ authenticatedPage: page }) => {
    const dialog = await openConfigure(page);

    await dialog.getByRole('combobox').click();
    await page.getByRole('option', { name: SETTING, exact: true }).click();
    await dialog.getByRole('button', { name: 'Add Field' }).click();

    const field = await settingField(dialog, SETTING);
    await expect(field).toHaveValue('');
    await field.fill(VALUE);
    await save(page, dialog);
  });

  test('the override survives a reload', async ({ authenticatedPage: page }) => {
    await page.reload();
    const dialog = await openConfigure(page);
    await expect(await settingField(dialog, SETTING)).toHaveValue(VALUE);
  });

  test('the override is scoped to its service body', async ({ authenticatedPage: page, request }) => {
    const { token } = await page.evaluate(() => JSON.parse(localStorage.getItem('session')));
    const headers = { Authorization: `Bearer ${token}` };

    const brooklyn = await request.get(`/api/v1/settings/serviceBody/${BROOKLYN.id}`, { headers });
    expect(await brooklyn.json()).toEqual([{ setting: SETTING, value: VALUE }]);

    const manhattan = await request.get('/api/v1/settings/serviceBody/1008', { headers });
    expect(await manhattan.json()).toEqual([]);
  });

  test('removes the override', async ({ authenticatedPage: page }) => {
    let dialog = await openConfigure(page);
    await dialog.getByRole('button', { name: '×' }).click();
    await expect(dialog.getByPlaceholder('Enter value')).toHaveCount(0);
    await save(page, dialog);

    dialog = await openConfigure(page);
    await expect(dialog.getByPlaceholder('Enter value')).toHaveCount(0);
  });
});
