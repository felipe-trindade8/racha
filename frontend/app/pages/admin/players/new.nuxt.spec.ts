import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import NewPlayerPage from './new.vue'
import PlayerForm from '~/components/PlayerForm.vue'
import { PlayerStatus, type Player, type PlayerPayload, type Position } from '~/types/player'

const positions = ref<Position[]>([{ id: 1, code: 'GK', name: 'Goalkeeper' }])
const loading = ref(false)
const { ensureLoaded, create, navigateTo } = vi.hoisted(() => ({
  ensureLoaded: vi.fn().mockResolvedValue(undefined),
  create: vi.fn(),
  navigateTo: vi.fn(),
}))

mockNuxtImport('usePositions', () => () => ({ positions, ensureLoaded }))
mockNuxtImport('usePlayers', () => () => ({ create, loading }))
mockNuxtImport('navigateTo', () => navigateTo)

const payload: PlayerPayload = {
  name: 'Marcos Cafu',
  nickname: null,
  phone: null,
  position_ids: [1],
  rating: 4,
  status: PlayerStatus.Active,
}

const created: Player = {
  id: 1,
  name: 'Marcos Cafu',
  nickname: null,
  rating: 4,
  status: PlayerStatus.Active,
  phone: null,
  positions: [],
  createdAt: '2026-06-12T00:00:00Z',
  updatedAt: '2026-06-12T00:00:00Z',
}

describe('new player page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    loading.value = false
  })

  it('creates the player and navigates back to the list on success', async () => {
    create.mockResolvedValue(created)
    const wrapper = await mountSuspended(NewPlayerPage)

    wrapper.findComponent(PlayerForm).vm.$emit('submit', payload)
    await flushPromises()

    expect(create).toHaveBeenCalledWith(payload)
    expect(navigateTo).toHaveBeenCalledWith('/admin/players')
  })

  it('surfaces a server validation error without navigating', async () => {
    create.mockRejectedValue({
      message: 'Validation failed',
      errors: { name: ['The name has already been taken.'] },
    })
    const wrapper = await mountSuspended(NewPlayerPage)

    wrapper.findComponent(PlayerForm).vm.$emit('submit', payload)
    await flushPromises()

    expect(navigateTo).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Validation failed')
    expect(wrapper.text()).toContain('The name has already been taken.')
  })
})
