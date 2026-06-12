/**
 * Player list state and mutations.
 *
 * Exposes the player list plus pagination metadata, loading/error flags and the
 * create/update/status mutations. The list is held in shared SSR-friendly state
 * via `useState`; business logic lives here, API communication in
 * {@link PlayerService} (see docs/coding-standards.md).
 */
import { PlayerService } from '~/services/PlayerService'
import type { ApiError, PaginationMeta } from '~/types/api'
import type { Player, PlayerPayload, PlayerQuery, PlayerStatus } from '~/types/player'

export function usePlayers() {
  const players = useState<Player[]>('players.list', () => [])
  const meta = useState<PaginationMeta | null>('players.meta', () => null)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)
  const service = new PlayerService()

  /**
   * Run a service call while tracking loading and error state. The error is
   * captured for consumers and re-thrown so callers can react to failures.
   */
  async function run<T>(action: () => Promise<T>): Promise<T> {
    loading.value = true
    error.value = null
    try {
      return await action()
    } catch (e) {
      error.value = e as ApiError
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Load a page of players into the shared list. */
  async function fetchList(query?: PlayerQuery) {
    await run(async () => {
      const { data, meta: pageMeta } = await service.list(query)
      players.value = data
      meta.value = pageMeta
    })
  }

  /** Fetch a single player without touching the list state. */
  function fetchOne(id: number) {
    return run(async () => (await service.show(id)).data)
  }

  /** Create a player and prepend it to the current list. */
  function create(payload: PlayerPayload) {
    return run(async () => {
      const { data } = await service.create(payload)
      players.value = [data, ...players.value]
      return data
    })
  }

  /** Update a player and replace it in the current list when present. */
  function update(id: number, payload: Partial<PlayerPayload>) {
    return run(async () => {
      const { data } = await service.update(id, payload)
      replaceInList(data)
      return data
    })
  }

  /** Change a player's status and replace it in the current list when present. */
  function updateStatus(id: number, status: PlayerStatus) {
    return run(async () => {
      const { data } = await service.updateStatus(id, status)
      replaceInList(data)
      return data
    })
  }

  function replaceInList(player: Player) {
    players.value = players.value.map((current) => (current.id === player.id ? player : current))
  }

  return { players, meta, loading, error, fetchList, fetchOne, create, update, updateStatus }
}
