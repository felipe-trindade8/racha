import { defineVitestConfig } from '@nuxt/test-utils/config'

export default defineVitestConfig({
  test: {
    // The Nuxt environment provides auto-imports, #app, #imports and
    // runtimeConfig so composables and components run as they do in the app.
    environment: 'nuxt',
  },
})
