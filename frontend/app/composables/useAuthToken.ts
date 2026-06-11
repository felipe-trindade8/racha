/**
 * Bearer token storage.
 *
 * The live value is held in shared `useState` so every call site — notably the
 * API client's header hook ({@link useApi}) — reads the same, synchronously
 * up-to-date token. (Independent `useCookie` refs do not sync within a tick, so
 * a token written during login would not be seen by a freshly-created cookie
 * ref on the immediately-following request.)
 *
 * It is mirrored to a cookie (not localStorage) for persistence and so it is
 * readable during SSR — the app runs in Nuxt's universal mode (see
 * docs/architecture.md). The cookie seeds the state on first load (the request
 * cookie on the server, the hydrated payload on the client).
 */
export function useAuthToken() {
  const cookie = useCookie<string | null>('auth_token', {
    default: () => null,
    sameSite: 'lax',
    secure: !import.meta.dev,
  })

  const token = useState<string | null>('auth.token', () => cookie.value)

  return computed<string | null>({
    get: () => token.value,
    set: (value) => {
      token.value = value
      cookie.value = value
    },
  })
}
