/**
 * Match contract types.
 *
 * These mirror the backend match endpoints (see docs/architecture.md and the
 * resources under `App\Http\Resources`: `GameMatchResource`,
 * `GameMatchTeamResource`, `TeamPlayerResource`, `GameMatchHistoryResource`).
 * Resource keys are camelCase, but the write-side keys (`team_name`,
 * `player_id`, `position_id`, `is_starter`, `team_a_result`, …) and the query
 * keys (`per_page`) stay snake_case on purpose — they reflect the JSON wire
 * contract, not frontend code style.
 *
 * The frontend uses the `Match` name (the backend uses `GameMatch` only to avoid
 * the PHP reserved word, a constraint that does not apply here).
 */
import type { Player } from '~/types/player'

/** Match lifecycle state, mirroring the backend `GameMatchStatusEnum`. */
export const MatchStatus = {
  Planned: 'planned',
  Finished: 'finished',
} as const

export type MatchStatus = (typeof MatchStatus)[keyof typeof MatchStatus]

/** A rostered player as returned by `TeamPlayerResource`. */
export interface TeamPlayerType {
  id: number
  playerId: number
  positionId: number | null
  gameRating: number | null
  isStarter: boolean
  /** Present when the roster is loaded with its player (detail view). */
  player?: Player
}

/**
 * A team within a match as returned by `GameMatchTeamResource`. `players` is
 * present on the detail and create responses and omitted from the list.
 */
export interface MatchTeamType {
  id: number
  teamName: string
  result: string | null
  players?: TeamPlayerType[]
}

/** A match as returned by `GameMatchResource`. */
export interface MatchType {
  id: number
  date: string
  status: MatchStatus
  teams: MatchTeamType[]
  createdAt: string
  updatedAt: string
}

/** One side of a history entry as returned by `GameMatchHistoryResource`. */
export interface MatchHistoryTeam {
  id: number
  teamName: string
  result: string | null
}

/** A finished match summary as returned by `GameMatchHistoryResource`. */
export interface MatchHistoryType {
  id: number
  date: string
  status: MatchStatus
  teamA: MatchHistoryTeam
  teamB: MatchHistoryTeam
}

/** A rostered player within a create payload. */
export type TeamPlayerPayload = {
  player_id: number
  position_id?: number | null
  is_starter?: boolean
}

/** A team within a create payload. */
export type MatchTeamPayload = {
  team_name: string
  players?: TeamPlayerPayload[]
}

/** Body accepted by create (`POST /matches`). */
export type MatchPayload = {
  date: string
  teams: MatchTeamPayload[]
}

/** Body accepted by update (`PUT /matches/{id}`); only the date is editable. */
export type MatchUpdatePayload = {
  date: string
}

/** A per-player rating within a score payload. */
export type PlayerRatingPayload = {
  team_player_id: number
  game_rating: number
}

/** Body accepted by the score endpoint (`PATCH /matches/{id}/score`). */
export type MatchScorePayload = {
  team_a_result: string
  team_b_result: string
  player_ratings?: PlayerRatingPayload[]
}

/** Query params accepted by the match listing endpoint. */
export type MatchQuery = {
  status?: MatchStatus
  date?: string
  per_page?: number
  page?: number
}

/** Query params accepted by the match history endpoint. */
export type MatchHistoryQuery = {
  per_page?: number
  page?: number
}
