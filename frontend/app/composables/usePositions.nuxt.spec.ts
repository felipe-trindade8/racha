import { describe, it, expect, vi, beforeEach } from 'vitest'
import { usePositions } from './usePositions'
import type { Position } from '~/types/player'

const positions: Position[] = [
  { id: 1, code: 'GK', name: 'Goalkeeper' },
  { id: 2, code: 'DEF', name: 'Defender' },
]

const list = vi.fn()

vi.mock('~/services/PositionService', () => ({
  PositionService: class {
    list = list
  },
}))

describe('usePositions', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    useState<Position[]>('positions.list').value = []
  })

  it('fetches the positions into shared state', async () => {
    list.mockResolvedValue({ data: positions })

    const { positions: state, ensureLoaded } = usePositions()
    await ensureLoaded()

    expect(list).toHaveBeenCalledOnce()
    expect(state.value).toEqual(positions)
  })

  it('does not refetch when the list is already loaded', async () => {
    useState<Position[]>('positions.list').value = positions

    const { ensureLoaded } = usePositions()
    await ensureLoaded()

    expect(list).not.toHaveBeenCalled()
  })
})
