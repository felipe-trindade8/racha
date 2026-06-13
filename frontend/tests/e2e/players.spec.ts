import { expect, test, type Page } from '@playwright/test'

// Credentials seeded by the backend DatabaseSeeder (admin@example.com).
const ADMIN_EMAIL = 'admin@example.com'
const ADMIN_PASSWORD = 'password'

/**
 * Open the login form and sign in as the seeded administrator. Waits for
 * network idle so the page is hydrated before filling (see auth.spec.ts).
 */
async function loginAsAdmin(page: Page) {
  await page.goto('/login')
  await page.waitForLoadState('networkidle')

  await page.getByLabel('Email').fill(ADMIN_EMAIL)
  await page.getByLabel('Password').fill(ADMIN_PASSWORD)
  await page.getByRole('button', { name: 'Sign in' }).click()
  await expect(page).toHaveURL(/\/$/)
}

test('an administrator creates a player', async ({ page }) => {
  await loginAsAdmin(page)

  await page.goto('/admin/players')
  await page.getByRole('link', { name: 'New player' }).click()
  await expect(page).toHaveURL(/\/admin\/players\/new$/)
  await page.waitForLoadState('networkidle')

  // Unique name so the assertion is independent of the seeded roster.
  const name = `Test Player ${Date.now()}`
  await page.getByLabel('Name', { exact: true }).fill(name)

  // Rating is required for the admin form. The Nuxt UI select opens on Space;
  // type-ahead picks the option and Enter commits it. The trailing assertion
  // confirms the selection landed before submitting.
  const rating = page.getByLabel('Rating')
  await rating.focus()
  await page.keyboard.press('Space')
  await page.keyboard.press('3')
  await page.keyboard.press('Enter')
  await expect(rating).toHaveText('3')

  await page.getByRole('button', { name: 'Create player' }).click()

  // Back on the roster; search by the unique name to find the new player
  // regardless of pagination or ordering.
  await expect(page).toHaveURL(/\/admin\/players$/)
  await page.getByPlaceholder('Search by name or nickname').fill(name)
  await expect(page.getByText(name)).toBeVisible()
})
