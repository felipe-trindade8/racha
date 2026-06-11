/**
 * Bearer token storage.
 *
 * Backed by a cookie (not localStorage) so the token is readable during SSR —
 * the app runs in Nuxt's universal mode (see docs/architecture.md). This is the
 * single source of truth read by the API client's auth-header hook
 * ({@link useApi}) and written by {@link useAuth}.
 */
export function useAuthToken() {
  return useCookie<string | null>('auth_token', {
    default: () => null,
    sameSite: 'lax',
    secure: !import.meta.dev,
  })
}
