import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import { useAuth } from './useAuth'
import { Role, type User } from '~/types/auth'

const adminUser: User = {
  id: 1,
  name: 'Admin',
  email: 'admin@racha.test',
  role: Role.Administrator,
  player_id: null,
}

const playerUser: User = {
  id: 2,
  name: 'Player',
  email: 'player@racha.test',
  role: Role.Player,
  player_id: 7,
}

const login = vi.fn()
const logout = vi.fn()
const me = vi.fn()

vi.mock('~/services/AuthService', () => ({
  AuthService: class {
    login = login
    logout = logout
    me = me
  },
}))

// Back the token with a plain ref so the flow is deterministic and independent
// of useCookie persistence internals (covered by Nuxt itself).
const { tokenRef } = vi.hoisted(() => ({ tokenRef: { value: null as string | null } }))
mockNuxtImport('useAuthToken', () => () => tokenRef)

describe('useAuth', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset shared state and token between tests.
    useState<User | null>('auth.user').value = null
    tokenRef.value = null
  })

  it('reports an unauthenticated user', () => {
    const { isAuthenticated, isAdmin } = useAuth()

    expect(isAuthenticated.value).toBe(false)
    expect(isAdmin.value).toBe(false)
  })

  it('derives isAdmin from an administrator role', () => {
    useState<User | null>('auth.user').value = adminUser
    const { isAuthenticated, isAdmin } = useAuth()

    expect(isAuthenticated.value).toBe(true)
    expect(isAdmin.value).toBe(true)
  })

  it('does not flag a player as admin', () => {
    useState<User | null>('auth.user').value = playerUser
    const { isAuthenticated, isAdmin } = useAuth()

    expect(isAuthenticated.value).toBe(true)
    expect(isAdmin.value).toBe(false)
  })

  it('stores the token and hydrates the user via me on login', async () => {
    login.mockResolvedValue({ data: { token: 'secret-token', user: adminUser } })
    me.mockResolvedValue({ data: adminUser })

    const { user, login: doLogin } = useAuth()
    await doLogin('admin@racha.test', 'password')

    expect(login).toHaveBeenCalledWith('admin@racha.test', 'password')
    expect(me).toHaveBeenCalledOnce()
    expect(tokenRef.value).toBe('secret-token')
    expect(user.value).toEqual(adminUser)
  })

  it('clears user and token on logout', async () => {
    logout.mockResolvedValue(undefined)
    useState<User | null>('auth.user').value = adminUser
    tokenRef.value = 'secret-token'

    const { user, logout: doLogout } = useAuth()
    await doLogout()

    expect(logout).toHaveBeenCalledOnce()
    expect(user.value).toBeNull()
    expect(tokenRef.value).toBeNull()
  })
})
