/**
 * Player API communication.
 *
 * Wraps the {@link useApi} client for the player REST endpoints via the typed
 * CRUD helpers on {@link BaseService}. Like the other `XxxService` classes it
 * owns API communication only — list state and business logic live in
 * {@link usePlayers} (see docs/coding-standards.md). The status transition is an
 * action rather than a REST verb, so it calls the client directly.
 */
import { BaseService } from '~/services/BaseService'
import type { Resource } from '~/types/api'
import type { Player, PlayerPayload, PlayerQuery, PlayerStatus } from '~/types/player'

export class PlayerService extends BaseService<Player> {
  constructor() {
    super('players')
  }

  /** Fetch a paginated list of players, optionally filtered by status. */
  override list(query?: PlayerQuery) {
    return super.list(query)
  }

  /** Fetch a single player by id. */
  show(id: number) {
    return super.get(id)
  }

  /** Create a player. */
  override create(payload: PlayerPayload) {
    return super.create(payload)
  }

  /** Update a player by id. */
  override update(id: number, payload: Partial<PlayerPayload>) {
    return super.update(id, payload)
  }

  /** Transition a player to a new status (active, inactive or injured). */
  updateStatus(id: number, status: PlayerStatus) {
    return this.client<Resource<Player>>(`${this.resource}/${id}/status`, {
      method: 'PATCH',
      body: { status },
    })
  }
}
