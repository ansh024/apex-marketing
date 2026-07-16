const { defineConfig, devices } = require('@playwright/test');

const baseURL = process.env.WP_LOCAL_URL || 'http://localhost:8892';
if (!/^http:\/\/(127\.0\.0\.1|localhost):8892$/.test(baseURL)) {
  throw new Error(`Refusing non-local Playwright baseURL: ${baseURL}`);
}

module.exports = defineConfig({
  testDir: './tests',
  timeout: 30_000,
  expect: { timeout: 5_000 },
  retries: 0,
  workers: 1,
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure'
  },
  projects: [
    { name: 'desktop', use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 1000 } } },
    { name: 'mobile', use: { ...devices['iPhone 13'], browserName: 'chromium' } },
    { name: 'reduced-motion', use: { ...devices['Desktop Chrome'], reducedMotion: 'reduce' } }
  ],
  reporter: [['list'], ['html', { open: 'never' }]]
});
