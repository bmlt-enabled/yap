import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  // Add action timeout and expect timeout for more stability
  timeout: 60000,
  expect: {
    timeout: 10000,
  },
  reporter: [
    ['html', { open: 'never' }],
    ['junit', { outputFile: 'tests/e2e-results.xml' }],
  ],
  use: {
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  webServer: {
    command: 'ENVIRONMENT=test php artisan migrate --force && ENVIRONMENT=test php artisan db:seed --class=TestEnvironmentSeeder --force && ENVIRONMENT=test php -S 127.0.0.1:8000 -t . server.php 2>/dev/null',
    url: 'http://127.0.0.1:8000',
    reuseExistingServer: false,
    timeout: 120 * 1000,
  },
});
