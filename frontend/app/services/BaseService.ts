/**
 * Base class for REST resource services (the `XxxService` pattern).
 *
 * Wraps the {@link useApi} client with typed CRUD helpers against a single
 * resource path so concrete services (e.g. `PlayerService`) only declare their
 * entity types. API communication lives here; business logic belongs in
 * composables (see docs/coding-standards.md).
 *
 * Example:
 *   class PlayerService extends BaseService<Player> {
 *     constructor() {
 *       super('players')
 *     }
 *   }
 */
import type { ApiClient } from '~/composables/useApi'
import type { Paginated, Resource } from '~/types/api'

export type QueryParams = Record<string, string | number | boolean | undefined>
export type Payload = Record<string, unknown>

export abstract class BaseService<TResource> {
  protected readonly client: ApiClient

  /**
   * @param resource Resource path relative to the API base, e.g. `players`.
   * @param client   Fetch client; defaults to the shared {@link useApi} client.
   */
  protected constructor(
    protected readonly resource: string,
    client: ApiClient = useApi().client,
  ) {
    this.client = client
  }

  /** Fetch a paginated list of resources. */
  protected list(query?: QueryParams) {
    return this.client<Paginated<TResource>>(this.resource, { query })
  }

  /** Fetch a single resource by id. */
  protected get(id: number | string) {
    return this.client<Resource<TResource>>(`${this.resource}/${id}`)
  }

  /** Create a resource. */
  protected create(payload: Payload) {
    return this.client<Resource<TResource>>(this.resource, { method: 'POST', body: payload })
  }

  /** Replace/update a resource by id. */
  protected update(id: number | string, payload: Payload) {
    return this.client<Resource<TResource>>(`${this.resource}/${id}`, {
      method: 'PUT',
      body: payload,
    })
  }

  /** Delete a resource by id. */
  protected remove(id: number | string) {
    return this.client(`${this.resource}/${id}`, { method: 'DELETE' })
  }
}
