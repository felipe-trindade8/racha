import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import MatchForm from './MatchForm.vue'
import { PlayerStatus, type Player, type Position } from '~/types/player'

function player(id: number, name: string): Player {
  return {
    id,
    name,
    nickname: null,
    rating: 3,
    status: PlayerStatus.Active,
    phone: null,
    positions: [],
    createdAt: '2026-06-12T00:00:00Z',
    updatedAt: '2026-06-12T00:00:00Z',
  }
}

const players = ref<Player[]>([player(1, 'Ana'), player(2, 'Bia'), player(3, 'Caio')])
const positions = ref<Position[]>([
  { id: 1, code: 'GK', name: 'Goalkeeper' },
  { id: 2, code: 'DEF', name: 'Defender' },
])
const fetchList = vi.fn().mockResolvedValue(undefined)
const ensureLoaded = vi.fn().mockResolvedValue(undefined)

mockNuxtImport('usePlayers', () => () => ({ players, fetchList }))
mockNuxtImport('usePositions', () => () => ({ positions, ensureLoaded }))

describe('MatchForm', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('loads only active players and the position reference data on mount', async () => {
    await mountSuspended(MatchForm)

    expect(fetchList).toHaveBeenCalledWith({ status: PlayerStatus.Active, per_page: 100 })
    expect(ensureLoaded).toHaveBeenCalled()
  })

  it('renders a date field and two team-name inputs', async () => {
    const wrapper = await mountSuspended(MatchForm)

    expect(wrapper.text()).toContain('Date')
    expect(wrapper.text()).toContain('Team 1 name')
    expect(wrapper.text()).toContain('Team 2 name')
  })

  it('does not emit when a team name is empty (client validation)', async () => {
    const wrapper = await mountSuspended(MatchForm)

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('Team name is required.')
    expect(wrapper.text()).toContain('Date is required.')
  })

  it('builds a valid payload with two teams and rosters on submit', async () => {
    const wrapper = await mountSuspended(MatchForm)
    const vm = wrapper.vm as unknown as {
      state: { date: string; teams: { teamName: string; roster: unknown[] }[] }
      addPlayer: (teamIndex: number, playerId: number) => void
    }

    vm.state.date = '2026-07-11'
    vm.state.teams[0]!.teamName = 'Reds'
    vm.state.teams[1]!.teamName = 'Blues'
    vm.addPlayer(0, 1)
    vm.addPlayer(1, 2)
    await flushPromises()

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted![0]![0]).toEqual({
      date: '2026-07-11',
      teams: [
        { team_name: 'Reds', players: [{ player_id: 1, position_id: null, is_starter: false }] },
        { team_name: 'Blues', players: [{ player_id: 2, position_id: null, is_starter: false }] },
      ],
    })
  })

  it('excludes a player rostered on one team from the other team picker (duplicate guard)', async () => {
    const wrapper = await mountSuspended(MatchForm)
    const vm = wrapper.vm as unknown as {
      addPlayer: (teamIndex: number, playerId: number) => void
      availableItems: { label: string; value: number }[]
    }

    expect(vm.availableItems.map((item) => item.value)).toEqual([1, 2, 3])

    vm.addPlayer(0, 1)
    await flushPromises()

    // Once rostered on team 1, the player is gone from the shared pool both
    // pickers draw from, so they cannot be added to team 2.
    expect(vm.availableItems.map((item) => item.value)).toEqual([2, 3])
  })

  it('surfaces the server error message', async () => {
    const wrapper = await mountSuspended(MatchForm, {
      props: { serverError: 'A player cannot be rostered more than once in a match.' },
    })

    expect(wrapper.text()).toContain('A player cannot be rostered more than once in a match.')
  })

  it('maps server validation errors onto the fields', async () => {
    const wrapper = await mountSuspended(MatchForm)

    await wrapper.setProps({ validationErrors: { date: ['The date field is required.'] } })
    await flushPromises()

    expect(wrapper.text()).toContain('The date field is required.')
  })
})
