import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import ScorePage from './score.vue'
import ScoreForm from '~/components/ScoreForm.vue'
import { MatchStatus, type MatchScorePayload, type MatchType } from '~/types/match'

const loading = ref(false)
const { fetchOne, score, navigateTo } = vi.hoisted(() => ({
  fetchOne: vi.fn(),
  score: vi.fn(),
  navigateTo: vi.fn(),
}))

mockNuxtImport('useMatches', () => () => ({ fetchOne, score, loading }))
mockNuxtImport('navigateTo', () => navigateTo)
mockNuxtImport('useRoute', () => () => ({ params: { id: '7' } }))

function makeMatch(status: MatchStatus, results: [string | null, string | null]): MatchType {
  return {
    id: 7,
    date: '2026-07-11',
    status,
    teams: [
      { id: 10, teamName: 'Reds', result: results[0], players: [] },
      { id: 11, teamName: 'Blues', result: results[1], players: [] },
    ],
    createdAt: '2026-07-01T00:00:00Z',
    updatedAt: '2026-07-01T00:00:00Z',
  }
}

const payload: MatchScorePayload = { team_a_result: '3', team_b_result: '1' }

describe('record score page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    loading.value = false
  })

  it('loads the match by route id and shows the form for a planned match', async () => {
    fetchOne.mockResolvedValue(makeMatch(MatchStatus.Planned, [null, null]))
    const wrapper = await mountSuspended(ScorePage)

    expect(fetchOne).toHaveBeenCalledWith(7)
    expect(wrapper.findComponent(ScoreForm).exists()).toBe(true)
  })

  it('records the score and reflects the finished state in place', async () => {
    fetchOne.mockResolvedValue(makeMatch(MatchStatus.Planned, [null, null]))
    score.mockResolvedValue(makeMatch(MatchStatus.Finished, ['3', '1']))
    const wrapper = await mountSuspended(ScorePage)

    wrapper.findComponent(ScoreForm).vm.$emit('submit', payload)
    await flushPromises()

    expect(score).toHaveBeenCalledWith(7, payload)
    // The form is gone and the finished result is shown.
    expect(wrapper.findComponent(ScoreForm).exists()).toBe(false)
    expect(wrapper.text()).toContain('3 – 1')
    expect(wrapper.text()).toContain('finished')
    expect(navigateTo).not.toHaveBeenCalled()
  })

  it('surfaces a server error without replacing the form', async () => {
    fetchOne.mockResolvedValue(makeMatch(MatchStatus.Planned, [null, null]))
    score.mockRejectedValue({
      message: 'A finished match cannot be scored again. Reopen it before scoring.',
      errors: { status: ['locked'] },
    })
    const wrapper = await mountSuspended(ScorePage)

    wrapper.findComponent(ScoreForm).vm.$emit('submit', payload)
    await flushPromises()

    expect(wrapper.findComponent(ScoreForm).exists()).toBe(true)
    expect(wrapper.text()).toContain('A finished match cannot be scored again.')
  })

  it('shows a read-only summary for an already-finished match', async () => {
    fetchOne.mockResolvedValue(makeMatch(MatchStatus.Finished, ['2', '0']))
    const wrapper = await mountSuspended(ScorePage)

    expect(wrapper.findComponent(ScoreForm).exists()).toBe(false)
    expect(wrapper.text()).toContain('2 – 0')
    expect(wrapper.text()).toContain('Reopen it to change the score.')
  })

  it('surfaces a load error', async () => {
    fetchOne.mockRejectedValue({ message: 'Match not found.' })
    const wrapper = await mountSuspended(ScorePage)

    expect(wrapper.text()).toContain('Match not found.')
    expect(wrapper.findComponent(ScoreForm).exists()).toBe(false)
  })
})
