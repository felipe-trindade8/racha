import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import PlayersPage from './index.vue'
import type { PaginationMeta } from '~/types/api'
import { PlayerStatus, type Player } from '~/types/player'

const cafu: Player = {
  id: 1,
  name: 'Marcos Cafu',
  nickname: 'Cafu',
  rating: 5,
  status: PlayerStatus.Active,
  phone: null,
  positions: [{ id: 1, code: 'RB', name: 'Right Back' }],
  createdAt: '2026-06-12T00:00:00Z',
  updatedAt: '2026-06-12T00:00:00Z',
}

const players = ref<Player[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(false)
const error = ref<{ message: string } | null>(null)
const fetchList = vi.fn()
const isAdmin = ref(true)

mockNuxtImport('usePlayers', () => () => ({ players, meta, loading, error, fetchList }))
mockNuxtImport('useAuth', () => () => ({ isAdmin }))

describe('players list page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    players.value = [cafu]
    meta.value = { current_page: 1, per_page: 15, total: 1, last_page: 1 }
    loading.value = false
    error.value = null
    isAdmin.value = true
    fetchList.mockResolvedValue(undefined)
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('loads the first page with default query on mount', async () => {
    await mountSuspended(PlayersPage)

    expect(fetchList).toHaveBeenCalledWith({
      search: undefined,
      status: undefined,
      per_page: 15,
      page: 1,
    })
  })

  it('renders the player with nickname, status and position badges', async () => {
    const wrapper = await mountSuspended(PlayersPage)

    expect(wrapper.text()).toContain('Marcos Cafu')
    expect(wrapper.text()).toContain('Cafu')
    expect(wrapper.text()).toContain('active')
    expect(wrapper.text()).toContain('RB')
  })

  it('links the create button to the new player page for an administrator', async () => {
    const wrapper = await mountSuspended(PlayersPage)

    const createLink = wrapper.findAll('a').find((a) => a.text().includes('New player'))
    expect(createLink).toBeDefined()
    expect(createLink!.attributes('href')).toBe('/admin/players/new')
  })

  it('hides the create button from a non-administrator', async () => {
    isAdmin.value = false
    const wrapper = await mountSuspended(PlayersPage)

    expect(wrapper.text()).not.toContain('New player')
  })

  it('shows an empty state when there are no players', async () => {
    players.value = []
    const wrapper = await mountSuspended(PlayersPage)

    expect(wrapper.text()).toContain('No players found.')
  })

  it('debounces search input and refetches from the first page', async () => {
    vi.useFakeTimers()
    const wrapper = await mountSuspended(PlayersPage)
    fetchList.mockClear()

    await wrapper.find('input').setValue('cafu')
    expect(fetchList).not.toHaveBeenCalled()

    vi.advanceTimersByTime(300)
    await flushPromises()

    expect(fetchList).toHaveBeenCalledWith(expect.objectContaining({ search: 'cafu', page: 1 }))
  })

  it('surfaces the error message from the composable', async () => {
    error.value = { message: 'Something went wrong.' }
    const wrapper = await mountSuspended(PlayersPage)

    expect(wrapper.text()).toContain('Something went wrong.')
  })
})
