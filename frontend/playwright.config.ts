import { defineConfig, devices } from '@playwright/test'

// E2E runs inside the dedicated `e2e` Docker service (see docker-compose.yml),
// which uses the official Playwright image with browsers preinstalled. The
// frontend container is alpine/musl, where Playwright browsers do not run.
//
// baseURL is environment-driven so the same config works both from the Compose
// network (http://frontend:3000) and against the local dev URL on the host
// (http://localhost:3000) via PLAYWRIGHT_BASE_URL.
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://frontend:3000'

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: 'list',
  use: {
    baseURL,
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
})
