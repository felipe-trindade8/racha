/**
 * Administrator-only route guard.
 *
 * Applied per-page via `definePageMeta({ middleware: 'admin' })`. Runs after the
 * global auth guard has hydrated the user, so non-administrators (players) are
 * redirected back to the home page.
 */
export default defineNuxtRouteMiddleware(() => {
  const { isAdmin } = useAuth()

  if (!isAdmin.value) {
    return navigateTo('/')
  }
})
