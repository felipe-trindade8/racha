import { defineVitestConfig } from '@nuxt/test-utils/config'

export default defineVitestConfig({
  test: {
    // The Nuxt environment provides auto-imports, #app, #imports and
    // runtimeConfig so composables and components run as they do in the app.
    environment: 'nuxt',
    // Playwright owns the E2E specs under tests/e2e — keep them out of Vitest.
    exclude: ['**/node_modules/**', '**/dist/**', '**/.nuxt/**', 'tests/e2e/**'],
  },
})
