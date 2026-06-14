/**
 * Position reference data.
 *
 * Exposes the canonical list of positions used by the player form's
 * multi-select. The list is reference data that rarely changes, so it is held
 * in shared SSR-friendly state via `useState` and fetched once: `ensureLoaded`
 * is a no-op when the list is already populated. API communication lives in
 * {@link PositionService} (see docs/coding-standards.md).
 */
import { PositionService } from '~/services/PositionService'
import type { Position } from '~/types/player'

export function usePositions() {
  const positions = useState<Position[]>('positions.list', () => [])
  const loading = ref(false)
  const service = new PositionService()

  /** Fetch the positions once; subsequent calls reuse the cached list. */
  async function ensureLoaded() {
    if (positions.value.length > 0) return

    loading.value = true
    try {
      const { data } = await service.list()
      positions.value = data
    } finally {
      loading.value = false
    }
  }

  return { positions, loading, ensureLoaded }
}
