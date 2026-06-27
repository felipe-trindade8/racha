import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { ref } from 'vue'
import MatchesPage from './index.vue'
import type { PaginationMeta } from '~/types/api'
import { MatchStatus, type MatchHistoryType, type MatchType } from '~/types/match'

const plannedMatch: MatchType = {
  id: 1,
  date: '2026-07-04',
  status: MatchStatus.Planned,
  teams: [
    { id: 10, teamName: 'Reds', result: null },
    { id: 11, teamName: 'Blues', result: null },
  ],
  createdAt: '2026-07-01T00:00:00Z',
  updatedAt: '2026-07-01T00:00:00Z',
}

const historyEntry: MatchHistoryType = {
  id: 2,
  date: '2026-06-20',
  status: MatchStatus.Finished,
  teamA: { id: 12, teamName: 'Reds', result: '3' },
  teamB: { id: 13, teamName: 'Blues', result: '1' },
}

const matches = ref<MatchType[]>([])
const meta = ref<PaginationMeta | null>(null)
const history = ref<MatchHistoryType[]>([])
const historyMeta = ref<PaginationMeta | null>(null)
const loading = ref(false)
const error = ref<{ message: string } | null>(null)
const fetchList = vi.fn()
const fetchHistory = vi.fn()
const isAdmin = ref(false)
// The global auth guard also calls useAuth(); give it the shape it reads so the
// mock satisfies both the page and the middleware.
const authUser = ref<{ id: number } | null>({ id: 1 })
const isAuthenticated = ref(true)
const fetchUser = vi.fn().mockResolvedValue(undefined)

mockNuxtImport('useMatches', () => () => ({
  matches,
  meta,
  history,
  historyMeta,
  loading,
  error,
  fetchList,
  fetchHistory,
}))

mockNuxtImport('useAuth', () => () => ({
  isAdmin,
  user: authUser,
  isAuthenticated,
  fetchUser,
}))

describe('matches list page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    matches.value = [plannedMatch]
    meta.value = { current_page: 1, per_page: 15, total: 1, last_page: 1 }
    history.value = [historyEntry]
    historyMeta.value = { current_page: 1, per_page: 15, total: 1, last_page: 1 }
    loading.value = false
    error.value = null
    isAdmin.value = false
    fetchList.mockResolvedValue(undefined)
    fetchHistory.mockResolvedValue(undefined)
  })

  it('loads the planned list with the default query on mount', async () => {
    await mountSuspended(MatchesPage)

    expect(fetchList).toHaveBeenCalledWith({
      status: MatchStatus.Planned,
      date: undefined,
      per_page: 15,
      page: 1,
    })
  })

  it('renders a planned match with team names and date', async () => {
    const wrapper = await mountSuspended(MatchesPage)

    expect(wrapper.text()).toContain('Reds vs Blues')
    expect(wrapper.text()).toContain('2026-07-04')
    expect(wrapper.text()).toContain('planned')
  })

  it('shows the score for a finished match in the list', async () => {
    matches.value = [
      {
        ...plannedMatch,
        status: MatchStatus.Finished,
        teams: [
          { id: 10, teamName: 'Reds', result: '2' },
          { id: 11, teamName: 'Blues', result: '0' },
        ],
      },
    ]
    const wrapper = await mountSuspended(MatchesPage)

    expect(wrapper.text()).toContain('2 – 0')
  })

  it('shows an empty state when there are no matches', async () => {
    matches.value = []
    const wrapper = await mountSuspended(MatchesPage)

    expect(wrapper.text()).toContain('No matches found.')
  })

  it('loads the finished-match history on mount', async () => {
    await mountSuspended(MatchesPage)

    expect(fetchHistory).toHaveBeenCalledWith({ per_page: 15, page: 1 })
  })

  it('surfaces the error message from the composable', async () => {
    error.value = { message: 'Something went wrong.' }
    const wrapper = await mountSuspended(MatchesPage)

    expect(wrapper.text()).toContain('Something went wrong.')
  })

  it('hides the New match button from non-administrators', async () => {
    const wrapper = await mountSuspended(MatchesPage)

    expect(wrapper.text()).not.toContain('New match')
  })

  it('shows the New match button to administrators', async () => {
    isAdmin.value = true
    const wrapper = await mountSuspended(MatchesPage)

    expect(wrapper.text()).toContain('New match')
  })
})
