/**
 * Player contract types.
 *
 * These mirror the backend player endpoints (see docs/architecture.md and the
 * resources under `App\Http\Resources`). The resource keys are camelCase, but
 * the write-side `position_ids` and the `per_page` query key stay snake_case on
 * purpose — they reflect the JSON wire contract, not frontend code style.
 */

/** Player status, mirroring the backend `PlayerStatusEnum`. */
export const PlayerStatus = {
  Active: 'active',
  Inactive: 'inactive',
  Injured: 'injured',
} as const

export type PlayerStatus = (typeof PlayerStatus)[keyof typeof PlayerStatus]

/** A position a player can play, as returned by `PositionResource`. */
export interface Position {
  id: number
  code: string
  name: string
}

/** A player as returned by `PlayerResource`. */
export interface Player {
  id: number
  name: string
  nickname: string | null
  rating: number
  status: PlayerStatus
  phone: string | null
  positions: Position[]
  createdAt: string
  updatedAt: string
}

/** Body accepted by create (`POST`) and update (`PUT`); update is partial. */
export type PlayerPayload = {
  name: string
  nickname?: string | null
  rating: number
  status?: PlayerStatus
  phone?: string | null
  position_ids?: number[]
}

/** Query params accepted by the player listing endpoint. */
export type PlayerQuery = {
  status?: PlayerStatus
  search?: string
  per_page?: number
  page?: number
}
