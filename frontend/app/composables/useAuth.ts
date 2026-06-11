/**
 * Authentication state and actions.
 *
 * Exposes the authenticated user plus derived flags and the login/logout flow.
 * The token is persisted by {@link useAuthToken} (a cookie, SSR-readable) and
 * injected into every API call by {@link useApi}; the user is held in shared
 * SSR-friendly state via `useState`. Business logic lives here, API
 * communication in {@link AuthService} (see docs/coding-standards.md).
 */
import { AuthService } from '~/services/AuthService'
import { Role, type User } from '~/types/auth'

export function useAuth() {
  const user = useState<User | null>('auth.user', () => null)
  const token = useAuthToken()
  const service = new AuthService()

  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => user.value?.role === Role.Administrator)

  /** Hydrate the user from `auth/me` using the stored token. */
  async function fetchUser() {
    const { data } = await service.me()
    user.value = data
  }

  /** Authenticate, persist the token and hydrate the user via `me`. */
  async function login(email: string, password: string) {
    const { data } = await service.login(email, password)
    token.value = data.token
    await fetchUser()
  }

  /** Revoke the token and clear all auth state. */
  async function logout() {
    try {
      await service.logout()
    } finally {
      user.value = null
      token.value = null
    }
  }

  return { user, isAuthenticated, isAdmin, login, logout, fetchUser }
}
