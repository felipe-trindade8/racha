/**
 * Match list/detail/history state and mutations.
 *
 * Exposes the match list plus pagination metadata, the finished-match history,
 * loading/error flags and the create/update/score mutations. List and history
 * are held in shared SSR-friendly state via `useState`; business logic lives
 * here, API communication in {@link MatchService} (see docs/coding-standards.md).
 */
import { MatchService } from '~/services/MatchService'
import type { ApiError, PaginationMeta } from '~/types/api'
import type {
  MatchHistoryQuery,
  MatchHistoryType,
  MatchPayload,
  MatchQuery,
  MatchScorePayload,
  MatchType,
  MatchUpdatePayload,
} from '~/types/match'

export function useMatches() {
  const matches = useState<MatchType[]>('matches.list', () => [])
  const meta = useState<PaginationMeta | null>('matches.meta', () => null)
  const history = useState<MatchHistoryType[]>('matches.history', () => [])
  const historyMeta = useState<PaginationMeta | null>('matches.historyMeta', () => null)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)
  const service = new MatchService()

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

  /** Load a page of matches into the shared list. */
  async function fetchList(query?: MatchQuery) {
    await run(async () => {
      const { data, meta: pageMeta } = await service.list(query)
      matches.value = data
      meta.value = pageMeta
    })
  }

  /** Fetch a single match with its teams and rosters, without touching the list. */
  function fetchOne(id: number) {
    return run(async () => (await service.show(id)).data)
  }

  /** Load a page of finished-match history into shared state. */
  async function fetchHistory(query?: MatchHistoryQuery) {
    await run(async () => {
      const { data, meta: pageMeta } = await service.history(query)
      history.value = data
      historyMeta.value = pageMeta
    })
  }

  /** Create a match and prepend it to the current list. */
  function create(payload: MatchPayload) {
    return run(async () => {
      const { data } = await service.create(payload)
      matches.value = [data, ...matches.value]
      return data
    })
  }

  /** Update a match and replace it in the current list when present. */
  function update(id: number, payload: MatchUpdatePayload) {
    return run(async () => {
      const { data } = await service.update(id, payload)
      replaceInList(data)
      return data
    })
  }

  /** Record a match's score and replace it in the current list when present. */
  function score(id: number, payload: MatchScorePayload) {
    return run(async () => {
      const { data } = await service.score(id, payload)
      replaceInList(data)
      return data
    })
  }

  function replaceInList(match: MatchType) {
    matches.value = matches.value.map((current) => (current.id === match.id ? match : current))
  }

  return {
    matches,
    meta,
    history,
    historyMeta,
    loading,
    error,
    fetchList,
    fetchOne,
    fetchHistory,
    create,
    update,
    score,
  }
}
