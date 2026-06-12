import { describe, it, expect, vi, beforeEach } from 'vitest'
import { usePlayers } from './usePlayers'
import type { ApiError, PaginationMeta } from '~/types/api'
import { PlayerStatus, type Player } from '~/types/player'

const player: Player = {
  id: 1,
  name: 'Cafu',
  nickname: 'Cafu',
  rating: 5,
  status: PlayerStatus.Active,
  phone: null,
  positions: [{ id: 1, code: 'DEF', name: 'Defender' }],
  createdAt: '2026-06-12T00:00:00Z',
  updatedAt: '2026-06-12T00:00:00Z',
}

const meta: PaginationMeta = { current_page: 1, per_page: 15, total: 1, last_page: 1 }

const list = vi.fn()
const show = vi.fn()
const create = vi.fn()
const update = vi.fn()
const updateStatus = vi.fn()

vi.mock('~/services/PlayerService', () => ({
  PlayerService: class {
    list = list
    show = show
    create = create
    update = update
    updateStatus = updateStatus
  },
}))

describe('usePlayers', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset shared state between tests.
    useState<Player[]>('players.list').value = []
    useState<PaginationMeta | null>('players.meta').value = null
  })

  it('starts with an empty list and no loading or error', () => {
    const { players, meta: pageMeta, loading, error } = usePlayers()

    expect(players.value).toEqual([])
    expect(pageMeta.value).toBeNull()
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('loads players and pagination meta on fetchList', async () => {
    list.mockResolvedValue({ data: [player], meta })

    const { players, meta: pageMeta, loading, fetchList } = usePlayers()
    await fetchList({ status: PlayerStatus.Active })

    expect(list).toHaveBeenCalledWith({ status: PlayerStatus.Active })
    expect(players.value).toEqual([player])
    expect(pageMeta.value).toEqual(meta)
    expect(loading.value).toBe(false)
  })

  it('captures the error and re-throws when a fetch fails', async () => {
    const failure: ApiError = { message: 'Forbidden' }
    list.mockRejectedValue(failure)

    const { error, loading, fetchList } = usePlayers()

    await expect(fetchList()).rejects.toBe(failure)
    expect(error.value).toEqual(failure)
    expect(loading.value).toBe(false)
  })

  it('prepends a created player to the list', async () => {
    const existing = { ...player, id: 2 }
    useState<Player[]>('players.list').value = [existing]
    create.mockResolvedValue({ data: player })

    const { players, create: doCreate } = usePlayers()
    const result = await doCreate({ name: 'Cafu', rating: 5 })

    expect(create).toHaveBeenCalledWith({ name: 'Cafu', rating: 5 })
    expect(result).toEqual(player)
    expect(players.value).toEqual([player, existing])
  })

  it('replaces an updated player in the list', async () => {
    useState<Player[]>('players.list').value = [player]
    const updated = { ...player, name: 'Marcos Cafu' }
    update.mockResolvedValue({ data: updated })

    const { players, update: doUpdate } = usePlayers()
    await doUpdate(player.id, { name: 'Marcos Cafu' })

    expect(update).toHaveBeenCalledWith(player.id, { name: 'Marcos Cafu' })
    expect(players.value).toEqual([updated])
  })

  it('replaces a player whose status changed in the list', async () => {
    useState<Player[]>('players.list').value = [player]
    const inactive = { ...player, status: PlayerStatus.Inactive }
    updateStatus.mockResolvedValue({ data: inactive })

    const { players, updateStatus: doUpdateStatus } = usePlayers()
    await doUpdateStatus(player.id, PlayerStatus.Inactive)

    expect(updateStatus).toHaveBeenCalledWith(player.id, PlayerStatus.Inactive)
    expect(players.value).toEqual([inactive])
  })
})
