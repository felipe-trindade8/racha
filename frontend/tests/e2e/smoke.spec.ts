import { expect, test } from '@playwright/test'

test('home redirects unauthenticated visitors to the login page', async ({ page }) => {
  await page.goto('/')

  // Home is now an authenticated route (see middleware/auth.global.ts), so an
  // unauthenticated visit lands on the login form — the smoke signal that the
  // app boots and SSR routing works.
  await expect(page).toHaveURL(/\/login$/)
  await expect(page.getByRole('button', { name: 'Sign in' })).toBeVisible()
})
