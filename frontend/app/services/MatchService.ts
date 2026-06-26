/**
 * Match API communication.
 *
 * Wraps the {@link useApi} client for the match endpoints via the typed CRUD
 * helpers on {@link BaseService}. Like the other `XxxService` classes it owns API
 * communication only — list/detail/history state and business logic live in
 * {@link useMatches} (see docs/coding-standards.md). The score transition and the
 * history listing are actions rather than plain REST verbs, so they call the
 * client directly.
 */
import { BaseService } from '~/services/BaseService'
import type { Paginated, Resource } from '~/types/api'
import type {
  MatchHistoryQuery,
  MatchHistoryType,
  MatchPayload,
  MatchQuery,
  MatchScorePayload,
  MatchType,
  MatchUpdatePayload,
} from '~/types/match'

export class MatchService extends BaseService<MatchType> {
  constructor() {
    super('matches')
  }

  /** Fetch a paginated list of matches, optionally filtered by status/date. */
  override list(query?: MatchQuery) {
    return super.list(query)
  }

  /** Fetch a single match with its teams and rosters. */
  show(id: number) {
    return super.get(id)
  }

  /** Create a match with its two teams and rosters. */
  override create(payload: MatchPayload) {
    return super.create(payload)
  }

  /** Update a match's date. */
  override update(id: number, payload: MatchUpdatePayload) {
    return super.update(id, payload)
  }

  /** Record a match's score and move it to finished. */
  score(id: number, payload: MatchScorePayload) {
    return this.client<Resource<MatchType>>(`${this.resource}/${id}/score`, {
      method: 'PATCH',
      body: payload,
    })
  }

  /** Fetch the paginated history of finished matches with results. */
  history(query?: MatchHistoryQuery) {
    return this.client<Paginated<MatchHistoryType>>(`${this.resource}/history`, { query })
  }
}
