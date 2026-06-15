import { expect, test, type Page } from '@playwright/test'

// Credentials seeded by the backend DatabaseSeeder (admin@example.com).
const ADMIN_EMAIL = 'admin@example.com'
const ADMIN_PASSWORD = 'password'

/**
 * Open the login form and sign in as the seeded administrator. Waits for
 * network idle so the page is hydrated before filling (see players.spec.ts).
 */
async function loginAsAdmin(page: Page) {
  await page.goto('/login')
  await page.waitForLoadState('networkidle')

  await page.getByLabel('Email').fill(ADMIN_EMAIL)
  await page.getByLabel('Password').fill(ADMIN_PASSWORD)
  await page.getByRole('button', { name: 'Sign in' }).click()
  await expect(page).toHaveURL(/\/$/)
}

test('an administrator sees a paid transaction reflected in the cash flow', async ({ page }) => {
  await loginAsAdmin(page)

  // Create an income transaction. Type (Income) and date (today) keep their
  // defaults, so only the description and amount need filling.
  const description = `Smoke income ${Date.now()}`
  await page.goto('/admin/finances')
  await page.waitForLoadState('networkidle')
  await page.getByRole('button', { name: 'New transaction' }).click()
  await page.getByLabel('Description').fill(description)
  await page.getByLabel('Amount', { exact: true }).fill('123.45')
  await page.getByRole('button', { name: 'Create transaction' }).click()

  // The new (open) transaction appears; mark it paid so it counts towards the
  // realized cash flow, then confirm the row flipped to paid.
  const card = page.locator('li', { hasText: description })
  await expect(card).toBeVisible()
  await card.getByRole('button', { name: 'Mark paid' }).click()
  await expect(card.getByRole('button', { name: 'Reopen' })).toBeVisible()

  // The cash-flow dashboard (paid by default) reflects the transaction: balance
  // and totals are shown and the current month appears in the breakdown.
  const currentMonth = new Date().toISOString().slice(0, 7)
  await page.goto('/admin/finances/cash-flow')
  await page.waitForLoadState('networkidle')

  // The totals card labels and the breakdown headers share text ("Balance",
  // "Income", "Expense"), so scope to the first match (the cards come first).
  await expect(page.getByRole('heading', { name: 'Cash flow' })).toBeVisible()
  await expect(page.getByText('Balance').first()).toBeVisible()
  await expect(page.getByText('Income').first()).toBeVisible()
  await expect(page.getByText('Expense').first()).toBeVisible()
  await expect(page.getByText('Monthly breakdown')).toBeVisible()
  await expect(page.getByText(currentMonth).first()).toBeVisible()
})
