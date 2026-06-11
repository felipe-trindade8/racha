/**
 * Global authentication guard.
 *
 * Runs on every navigation. When a token is present but the user state is empty
 * (SSR first paint or a hard refresh) it hydrates the user via `me`; an invalid
 * token is cleared. Unauthenticated visitors are sent to the login page, and
 * authenticated visitors are kept away from it.
 */
export default defineNuxtRouteMiddleware(async (to) => {
  const { user, isAuthenticated, fetchUser } = useAuth()
  const token = useAuthToken()

  if (token.value && !user.value) {
    try {
      await fetchUser()
    } catch {
      token.value = null
    }
  }

  const isLoginRoute = to.path === '/login'

  if (!isAuthenticated.value && !isLoginRoute) {
    return navigateTo('/login')
  }

  if (isAuthenticated.value && isLoginRoute) {
    return navigateTo('/')
  }
})
