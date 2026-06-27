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

test('an administrator creates a match, records the score, and sees it in history', async ({
  page,
}) => {
  await login(page)

  // Create a planned match. Two team names are enough; rosters are optional and
  // the score does not require them.
  const stamp = Date.now()
  const teamA = `Reds ${stamp}`
  const teamB = `Blues ${stamp}`

  await page.goto('/matches')
  await page.getByRole('link', { name: 'New match' }).click()
  await expect(page).toHaveURL(/\/admin\/matches\/new$/)
  await page.waitForLoadState('networkidle')

  await page.getByLabel('Date').fill('2026-07-11')
  await page.getByLabel('Team 1 name').fill(teamA)
  await page.getByLabel('Team 2 name').fill(teamB)
  await page.getByRole('button', { name: 'Create match' }).click()

  // Back on the list; the new planned match is shown.
  await expect(page).toHaveURL(/\/matches$/)
  await page.waitForLoadState('networkidle')
  const card = page.locator('li', { hasText: teamA })
  await expect(card).toBeVisible()

  // Open the score screen for this planned match and finish it.
  await card.getByRole('link', { name: /Record score/ }).click()
  await expect(page).toHaveURL(/\/admin\/matches\/\d+\/score$/)
  await page.waitForLoadState('networkidle')

  // Two result inputs share the "Result" label, one per team.
  await page.getByLabel('Result').nth(0).fill('3')
  await page.getByLabel('Result').nth(1).fill('1')
  await page.getByRole('button', { name: 'Finish match' }).click()

  // The screen reflects the finished state in place.
  await expect(page.getByText('3 – 1')).toBeVisible()
  await expect(page.getByText('finished', { exact: true })).toBeVisible()

  // The finished match now appears under the History tab with its result.
  await page.goto('/matches')
  await page.waitForLoadState('networkidle')
  await page.getByRole('tab', { name: 'History' }).click()
  await expect(page.locator('li', { hasText: teamA })).toContainText('3 – 1')
})
