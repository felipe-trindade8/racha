/**
 * Position API communication.
 *
 * Wraps the {@link useApi} client for the read-only positions reference
 * endpoint. The list is small and unpaginated (`{ data: [...] }`), so it does
 * not use the {@link BaseService} pagination helpers. API communication lives
 * here; caching and state live in {@link usePositions} (see
 * docs/coding-standards.md).
 */
import type { ApiClient } from '~/composables/useApi'
import type { Resource } from '~/types/api'
import type { Position } from '~/types/player'

export class PositionService {
  private readonly client: ApiClient

  constructor(client: ApiClient = useApi().client) {
    this.client = client
  }

  /** Fetch the full reference list of positions. */
  list() {
    return this.client<Resource<Position[]>>('positions')
  }
}
