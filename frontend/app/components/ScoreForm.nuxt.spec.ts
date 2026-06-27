import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import ScoreForm from './ScoreForm.vue'
import { MatchStatus, type MatchType } from '~/types/match'
import { PlayerStatus, type Player } from '~/types/player'

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

const match: MatchType = {
  id: 7,
  date: '2026-07-11',
  status: MatchStatus.Planned,
  teams: [
    {
      id: 10,
      teamName: 'Reds',
      result: null,
      players: [
        {
          id: 100,
          playerId: 1,
          positionId: null,
          gameRating: null,
          isStarter: true,
          player: player(1, 'Ana'),
        },
      ],
    },
    {
      id: 11,
      teamName: 'Blues',
      result: null,
      players: [
        {
          id: 200,
          playerId: 2,
          positionId: null,
          gameRating: null,
          isStarter: true,
          player: player(2, 'Bia'),
        },
      ],
    },
  ],
  createdAt: '2026-07-01T00:00:00Z',
  updatedAt: '2026-07-01T00:00:00Z',
}

describe('ScoreForm', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders a result input and a rating select for each team', async () => {
    const wrapper = await mountSuspended(ScoreForm, { props: { match } })

    expect(wrapper.text()).toContain('Reds')
    expect(wrapper.text()).toContain('Blues')
    expect(wrapper.text()).toContain('Ana')
    expect(wrapper.text()).toContain('Bia')
    // One result field per team.
    expect(wrapper.findAll('input').length).toBeGreaterThanOrEqual(2)
  })

  it('does not emit when a result is empty (client validation)', async () => {
    const wrapper = await mountSuspended(ScoreForm, { props: { match } })

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('Result is required.')
  })

  it('builds a payload with both results and only the chosen ratings', async () => {
    const wrapper = await mountSuspended(ScoreForm, { props: { match } })
    const vm = wrapper.vm as unknown as {
      state: { results: [string, string] }
      ratings: Record<number, number | undefined>
    }

    vm.state.results[0] = '3'
    vm.state.results[1] = '1'
    // Rate only Ana (team player 100); leave Bia unrated.
    vm.ratings[100] = 4
    await flushPromises()

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted![0]![0]).toEqual({
      team_a_result: '3',
      team_b_result: '1',
      player_ratings: [{ team_player_id: 100, game_rating: 4 }],
    })
  })

  it('omits player_ratings when no rating is chosen', async () => {
    const wrapper = await mountSuspended(ScoreForm, { props: { match } })
    const vm = wrapper.vm as unknown as { state: { results: [string, string] } }

    vm.state.results[0] = '2'
    vm.state.results[1] = '2'
    await flushPromises()

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const payload = wrapper.emitted('submit')![0]![0]
    expect(payload).not.toHaveProperty('player_ratings')
    expect(payload).toMatchObject({ team_a_result: '2', team_b_result: '2' })
  })

  it('surfaces the server error message', async () => {
    const wrapper = await mountSuspended(ScoreForm, {
      props: {
        match,
        serverError: 'A finished match cannot be scored again. Reopen it before scoring.',
      },
    })

    expect(wrapper.text()).toContain('A finished match cannot be scored again.')
  })

  it('maps server validation errors onto the result fields', async () => {
    const wrapper = await mountSuspended(ScoreForm, { props: { match } })

    await wrapper.setProps({
      validationErrors: { team_a_result: ['The team a result field is required.'] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('The team a result field is required.')
  })
})
