import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useMatches } from './useMatches'
import type { ApiError, PaginationMeta } from '~/types/api'
import { MatchStatus, type MatchHistoryType, type MatchType } from '~/types/match'

const match: MatchType = {
  id: 1,
  date: '2026-07-04',
  status: MatchStatus.Planned,
  teams: [
    { id: 10, teamName: 'Team A', result: null },
    { id: 11, teamName: 'Team B', result: null },
  ],
  createdAt: '2026-07-01T00:00:00Z',
  updatedAt: '2026-07-01T00:00:00Z',
}

const historyEntry: MatchHistoryType = {
  id: 1,
  date: '2026-06-20',
  status: MatchStatus.Finished,
  teamA: { id: 10, teamName: 'Team A', result: '3' },
  teamB: { id: 11, teamName: 'Team B', result: '1' },
}

const meta: PaginationMeta = { current_page: 1, per_page: 15, total: 1, last_page: 1 }

const list = vi.fn()
const show = vi.fn()
const create = vi.fn()
const update = vi.fn()
const score = vi.fn()
const history = vi.fn()

vi.mock('~/services/MatchService', () => ({
  MatchService: class {
    list = list
    show = show
    create = create
    update = update
    score = score
    history = history
  },
}))

describe('useMatches', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset shared state between tests.
    useState<MatchType[]>('matches.list').value = []
    useState<PaginationMeta | null>('matches.meta').value = null
    useState<MatchHistoryType[]>('matches.history').value = []
    useState<PaginationMeta | null>('matches.historyMeta').value = null
  })

  it('starts with empty list, history, and no loading or error', () => {
    const { matches, meta: pageMeta, history: hist, historyMeta, loading, error } = useMatches()

    expect(matches.value).toEqual([])
    expect(pageMeta.value).toBeNull()
    expect(hist.value).toEqual([])
    expect(historyMeta.value).toBeNull()
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('loads matches and pagination meta on fetchList', async () => {
    list.mockResolvedValue({ data: [match], meta })

    const { matches, meta: pageMeta, fetchList } = useMatches()
    await fetchList({ status: MatchStatus.Planned, date: '2026-07-04' })

    expect(list).toHaveBeenCalledWith({ status: MatchStatus.Planned, date: '2026-07-04' })
    expect(matches.value).toEqual([match])
    expect(pageMeta.value).toEqual(meta)
  })

  it('returns a single match on fetchOne without touching the list', async () => {
    show.mockResolvedValue({ data: match })

    const { matches, fetchOne } = useMatches()
    const result = await fetchOne(match.id)

    expect(show).toHaveBeenCalledWith(match.id)
    expect(result).toEqual(match)
    expect(matches.value).toEqual([])
  })

  it('loads finished-match history and meta on fetchHistory', async () => {
    history.mockResolvedValue({ data: [historyEntry], meta })

    const { history: hist, historyMeta, fetchHistory } = useMatches()
    await fetchHistory({ per_page: 10 })

    expect(history).toHaveBeenCalledWith({ per_page: 10 })
    expect(hist.value).toEqual([historyEntry])
    expect(historyMeta.value).toEqual(meta)
  })

  it('captures the error and re-throws when a fetch fails', async () => {
    const failure: ApiError = { message: 'Forbidden' }
    list.mockRejectedValue(failure)

    const { error, loading, fetchList } = useMatches()

    await expect(fetchList()).rejects.toBe(failure)
    expect(error.value).toEqual(failure)
    expect(loading.value).toBe(false)
  })

  it('prepends a created match to the list', async () => {
    const existing = { ...match, id: 2 }
    useState<MatchType[]>('matches.list').value = [existing]
    create.mockResolvedValue({ data: match })

    const { matches, create: doCreate } = useMatches()
    const result = await doCreate({
      date: '2026-07-04',
      teams: [
        { team_name: 'Team A', players: [{ player_id: 1 }] },
        { team_name: 'Team B', players: [{ player_id: 2 }] },
      ],
    })

    expect(result).toEqual(match)
    expect(matches.value).toEqual([match, existing])
  })

  it('replaces an updated match in the list', async () => {
    useState<MatchType[]>('matches.list').value = [match]
    const updated = { ...match, date: '2026-07-11' }
    update.mockResolvedValue({ data: updated })

    const { matches, update: doUpdate } = useMatches()
    await doUpdate(match.id, { date: '2026-07-11' })

    expect(update).toHaveBeenCalledWith(match.id, { date: '2026-07-11' })
    expect(matches.value).toEqual([updated])
  })

  it('replaces a scored match in the list', async () => {
    useState<MatchType[]>('matches.list').value = [match]
    const finished = { ...match, status: MatchStatus.Finished }
    score.mockResolvedValue({ data: finished })

    const { matches, score: doScore } = useMatches()
    await doScore(match.id, { team_a_result: '3', team_b_result: '1' })

    expect(score).toHaveBeenCalledWith(match.id, { team_a_result: '3', team_b_result: '1' })
    expect(matches.value).toEqual([finished])
  })
})
