import { expect, test, type Page } from '@playwright/test'

// Credentials seeded by the backend DatabaseSeeder (admin@example.com).
const ADMIN_EMAIL = 'admin@example.com'
const ADMIN_PASSWORD = 'password'

/**
 * Open the login form and sign in. Waits for network idle so the page is
 * hydrated before filling — otherwise the dev server may receive input before
 * the form's client handlers are attached.
 */
async function login(page: Page) {
  await page.goto('/login')
  await page.waitForLoadState('networkidle')

  await page.getByLabel('Email').fill(ADMIN_EMAIL)
  await page.getByLabel('Password').fill(ADMIN_PASSWORD)
  await page.getByRole('button', { name: 'Sign in' }).click()
}

test('redirects unauthenticated visitors to the login page', async ({ page }) => {
  await page.goto('/')

  await expect(page).toHaveURL(/\/login$/)
})

test('logs in, lands on home and logs out', async ({ page }) => {
  await login(page)

  await expect(page).toHaveURL(/\/$/)
  await expect(page.getByRole('heading', { name: /Welcome/ })).toBeVisible()

  await page.getByRole('button', { name: 'Logout' }).click()

  await expect(page).toHaveURL(/\/login$/)
})

test('lets an administrator open the admin area', async ({ page }) => {
  await login(page)

  await page.getByRole('link', { name: 'Admin area' }).click()

  await expect(page).toHaveURL(/\/admin$/)
  await expect(page.getByRole('heading', { name: 'Admin area' })).toBeVisible()
})
