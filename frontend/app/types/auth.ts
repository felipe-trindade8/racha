/**
 * Auth contract types.
 *
 * These mirror the backend auth endpoints (see docs/architecture.md and the
 * Sanctum controllers under `App\Http\Controllers\Api\V1\Auth`). `player_id`
 * stays snake_case on purpose — it reflects the JSON wire contract (a DB
 * column), not frontend code style.
 */

/** User roles, mirroring the backend `RoleEnum`. */
export const Role = {
  Administrator: 'administrator',
  Player: 'player',
} as const

export type Role = (typeof Role)[keyof typeof Role]

/** Authenticated user as returned by `auth/me` and `auth/login`. */
export interface User {
  id: number
  name: string
  email: string
  role: Role
  player_id: number | null
}

/** Payload sent to `auth/login`. */
export interface LoginCredentials {
  email: string
  password: string
}

/** `data` payload returned by `auth/login`. */
export interface LoginResult {
  token: string
  user: User
}
