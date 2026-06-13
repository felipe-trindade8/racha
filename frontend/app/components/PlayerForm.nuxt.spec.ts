import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import PlayerForm from './PlayerForm.vue'
import { PlayerStatus, type Player, type Position } from '~/types/player'

const positions = ref<Position[]>([
  { id: 1, code: 'GK', name: 'Goalkeeper' },
  { id: 2, code: 'DEF', name: 'Defender' },
])
const ensureLoaded = vi.fn().mockResolvedValue(undefined)

mockNuxtImport('usePositions', () => () => ({ positions, ensureLoaded }))

const player: Player = {
  id: 7,
  name: 'Marcos Cafu',
  nickname: 'Cafu',
  rating: 4,
  status: PlayerStatus.Active,
  phone: '+55 11 99999-0000',
  positions: [{ id: 2, code: 'DEF', name: 'Defender' }],
  createdAt: '2026-06-12T00:00:00Z',
  updatedAt: '2026-06-12T00:00:00Z',
}

describe('PlayerForm', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('loads the positions reference data on mount', async () => {
    await mountSuspended(PlayerForm)
    expect(ensureLoaded).toHaveBeenCalled()
  })

  it('shows rating and status fields for the full (admin) form', async () => {
    const wrapper = await mountSuspended(PlayerForm)

    expect(wrapper.text()).toContain('Rating')
    expect(wrapper.text()).toContain('Status')
    expect(wrapper.text()).toContain('Positions')
  })

  it('hides rating and status in restricted mode', async () => {
    const wrapper = await mountSuspended(PlayerForm, { props: { restricted: true } })

    expect(wrapper.text()).not.toContain('Rating')
    expect(wrapper.text()).not.toContain('Status')
    expect(wrapper.text()).toContain('Positions')
  })

  it('emits a payload built from the initial values on submit', async () => {
    const wrapper = await mountSuspended(PlayerForm, { props: { initialValues: player } })

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted![0]![0]).toEqual({
      name: 'Marcos Cafu',
      nickname: 'Cafu',
      phone: '+55 11 99999-0000',
      position_ids: [2],
      rating: 4,
      status: PlayerStatus.Active,
    })
  })

  it('omits rating and status from the payload in restricted mode', async () => {
    const wrapper = await mountSuspended(PlayerForm, {
      props: { initialValues: player, restricted: true },
    })

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const payload = wrapper.emitted('submit')![0]![0]
    expect(payload).not.toHaveProperty('rating')
    expect(payload).not.toHaveProperty('status')
    expect(payload).toMatchObject({ name: 'Marcos Cafu', position_ids: [2] })
  })

  it('does not emit when the name is empty (client validation)', async () => {
    const wrapper = await mountSuspended(PlayerForm)

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('Name is required.')
  })

  it('surfaces the server error message', async () => {
    const wrapper = await mountSuspended(PlayerForm, {
      props: { serverError: 'Something went wrong.' },
    })

    expect(wrapper.text()).toContain('Something went wrong.')
  })

  it('maps server validation errors onto the fields', async () => {
    const wrapper = await mountSuspended(PlayerForm)

    await wrapper.setProps({ validationErrors: { name: ['The name has already been taken.'] } })
    await flushPromises()

    expect(wrapper.text()).toContain('The name has already been taken.')
  })
})
