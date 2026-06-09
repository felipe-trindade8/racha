// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-01-01',
  devtools: { enabled: true },

  modules: ['@nuxt/ui'],

  css: ['~/assets/css/main.css'],

  // Universal (SSR) mode is intentional — see docs/architecture.md.
  ssr: true,

  typescript: {
    strict: true,
    typeCheck: false,
  },

  devServer: {
    host: '0.0.0.0',
    port: 3000,
  },

  // Allow the Compose-network hostname so the `e2e` service (and any other
  // in-network client) can reach the dev server. Vite blocks unknown hosts by
  // default; `frontend` is the service name on the Compose network.
  vite: {
    server: {
      allowedHosts: ['frontend'],
    },
  },

  runtimeConfig: {
    // Private — only available server-side (SSR). Nuxt reaches the backend
    // over the internal Docker network hostname.
    // Override with NUXT_API_BASE_INTERNAL.
    apiBaseInternal: 'http://backend:8000/api/v1',

    public: {
      // Public — exposed to the browser. The browser reaches the backend
      // over the published host port.
      // Override with NUXT_PUBLIC_API_BASE.
      apiBase: 'http://localhost:8000/api/v1',
    },
  },
})
