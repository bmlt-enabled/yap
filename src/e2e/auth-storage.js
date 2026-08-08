import { existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname } from 'node:path';

export const authFile = 'e2e/.auth/admin.json';

/** In-memory copy; persisted to authFile so retries survive worker state resets. */
let cachedAuthStorage = null;

export function invalidateCachedAuth() {
  cachedAuthStorage = null;
  if (existsSync(authFile)) {
    rmSync(authFile, { force: true });
  }
}

export async function login(page, baseURL, username, password) {
  await page.goto(`${baseURL}/admin/login`);
  await page.waitForLoadState('networkidle');

  const usernameField = page.getByLabel(/username/i).or(page.getByPlaceholder(/username/i)).or(page.locator('input[type="text"]').first());
  const passwordField = page.getByLabel(/password/i).or(page.locator('input[type="password"]'));

  await usernameField.fill(username);
  await passwordField.fill(password);
  await page.getByRole('button', { name: /sign in/i }).click();
  await page.waitForURL('**/dashboard', { timeout: 15000 });
  await page.waitForTimeout(1000);
}

function loadAuthStorageFromDisk() {
  if (!existsSync(authFile)) {
    return null;
  }

  return JSON.parse(readFileSync(authFile, 'utf8'));
}

function persistAuthStorage(storage) {
  mkdirSync(dirname(authFile), { recursive: true });
  writeFileSync(authFile, JSON.stringify(storage));
  cachedAuthStorage = storage;
}

export async function bootstrapAuthStorage(browser, baseURL) {
  const bootstrap = await browser.newContext();
  const page = await bootstrap.newPage();
  await login(page, baseURL, 'admin', 'admin');
  const storage = await bootstrap.storageState();
  await bootstrap.close();
  persistAuthStorage(storage);
  return storage;
}

export async function getAuthStorage(browser, baseURL) {
  if (cachedAuthStorage) {
    return cachedAuthStorage;
  }

  const fromDisk = loadAuthStorageFromDisk();
  if (fromDisk) {
    cachedAuthStorage = fromDisk;
    return fromDisk;
  }

  return bootstrapAuthStorage(browser, baseURL);
}
