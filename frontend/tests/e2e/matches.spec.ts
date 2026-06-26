import { expect, test, type Page } from '@playwright/test'

// Credentials seeded by the backend DatabaseSeeder (admin@example.com).
const ADMIN_EMAIL = 'admin@example.com'
const ADMIN_PASSWORD = 'password'

/** Open the login form and sign in as the seeded administrator (see auth.spec.ts). */
async function login(page: Page) {
  await page.goto('/login')
  await page.waitForLoadState('networkidle')

  await page.getByLabel('Email').fill(ADMIN_EMAIL)
  await page.getByLabel('Password').fill(ADMIN_PASSWORD)
  await page.getByRole('button', { name: 'Sign in' }).click()
  await expect(page).toHaveURL(/\/$/)
}

test('the matches page shows the planned and history tabs', async ({ page }) => {
  await login(page)

  // Reachable from the home hub by every role.
  await page.getByRole('link', { name: 'Matches' }).click()
  await expect(page).toHaveURL(/\/matches$/)
  await page.waitForLoadState('networkidle')

  await expect(page.getByRole('heading', { name: 'Matches' })).toBeVisible()
  await expect(page.getByRole('tab', { name: 'Planned' })).toBeVisible()
  await expect(page.getByRole('tab', { name: 'History' })).toBeVisible()

  // The planned view exposes the status/date filter.
  await expect(page.getByLabel('Date')).toBeVisible()

  // Switching to History activates that tab.
  await page.getByRole('tab', { name: 'History' }).click()
  await expect(page.getByRole('tab', { name: 'History' })).toHaveAttribute('aria-selected', 'true')
})
