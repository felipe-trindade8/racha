/**
 * Auth API communication.
 *
 * Wraps the {@link useApi} client for the Sanctum auth endpoints. Like the other
 * `XxxService` classes it owns API communication only — token persistence and
 * state derivation live in {@link useAuth} (see docs/coding-standards.md). The
 * auth routes are actions rather than a REST resource, so this service calls the
 * client directly instead of the CRUD helpers on {@link BaseService}.
 */
import { BaseService } from '~/services/BaseService'
import type { Resource } from '~/types/api'
import type { LoginResult, User } from '~/types/auth'

export class AuthService extends BaseService {
  constructor() {
    super('auth')
  }

  /** Authenticate and receive a token + user. */
  login(email: string, password: string) {
    return this.client<Resource<LoginResult>>('auth/login', {
      method: 'POST',
      body: { email, password },
    })
  }

  /** Revoke the current token. */
  logout() {
    return this.client('auth/logout', { method: 'POST' })
  }

  /** Fetch the currently authenticated user. */
  me() {
    return this.client<Resource<User>>('auth/me')
  }
}
