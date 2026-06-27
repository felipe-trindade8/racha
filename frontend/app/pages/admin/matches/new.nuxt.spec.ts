import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import NewMatchPage from './new.vue'
import MatchForm from '~/components/MatchForm.vue'
import type { Player, Position } from '~/types/player'
import { MatchStatus, type MatchPayload, type MatchType } from '~/types/match'

const players = ref<Player[]>([])
const positions = ref<Position[]>([])
const loading = ref(false)
const { fetchList, ensureLoaded, create, navigateTo } = vi.hoisted(() => ({
  fetchList: vi.fn().mockResolvedValue(undefined),
  ensureLoaded: vi.fn().mockResolvedValue(undefined),
  create: vi.fn(),
  navigateTo: vi.fn(),
}))

mockNuxtImport('usePlayers', () => () => ({ players, fetchList }))
mockNuxtImport('usePositions', () => () => ({ positions, ensureLoaded }))
mockNuxtImport('useMatches', () => () => ({ create, loading }))
mockNuxtImport('navigateTo', () => navigateTo)

const payload: MatchPayload = {
  date: '2026-07-11',
  teams: [
    { team_name: 'Reds', players: [{ player_id: 1, position_id: null, is_starter: false }] },
    { team_name: 'Blues', players: [{ player_id: 2, position_id: null, is_starter: false }] },
  ],
}

const created: MatchType = {
  id: 1,
  date: '2026-07-11',
  status: MatchStatus.Planned,
  teams: [
    { id: 10, teamName: 'Reds', result: null },
    { id: 11, teamName: 'Blues', result: null },
  ],
  createdAt: '2026-07-01T00:00:00Z',
  updatedAt: '2026-07-01T00:00:00Z',
}

describe('new match page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    loading.value = false
    players.value = []
    positions.value = []
  })

  it('creates the match and navigates to the list on success', async () => {
    create.mockResolvedValue(created)
    const wrapper = await mountSuspended(NewMatchPage)

    wrapper.findComponent(MatchForm).vm.$emit('submit', payload)
    await flushPromises()

    expect(create).toHaveBeenCalledWith(payload)
    expect(navigateTo).toHaveBeenCalledWith('/matches')
  })

  it('surfaces a server validation error without navigating', async () => {
    create.mockRejectedValue({
      message: 'Validation failed',
      errors: { teams: ['A match must have exactly two teams.'] },
    })
    const wrapper = await mountSuspended(NewMatchPage)

    wrapper.findComponent(MatchForm).vm.$emit('submit', payload)
    await flushPromises()

    expect(navigateTo).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Validation failed')
  })
})
