import { expect, test } from '@playwright/test'

test('landing page loads', async ({ page }) => {
  await page.goto('/')

  // The heading and button only render on a successful HTML response, so these
  // are the smoke signal. (Asserting on the first raw response status is flaky
  // against the dev server's on-demand compilation on a cold start.)
  await expect(page.getByRole('heading', { name: 'Racha' })).toBeVisible()
  await expect(page.getByRole('button', { name: 'It works' })).toBeVisible()
})
