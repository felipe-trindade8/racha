/**
 * Shared API contract types.
 *
 * These mirror the backend response envelope documented in
 * docs/architecture.md and built through `App\Helpers\ApiResponse`. Keys inside
 * `meta` stay snake_case on purpose — they reflect the JSON wire contract, not
 * frontend code style.
 */

/** Field-level validation errors keyed by input name. */
export type ValidationErrors = Record<string, string[]>

/**
 * Normalized error shape every consumer can rely on, regardless of whether the
 * failure came from the backend envelope, a network error or an unexpected
 * exception.
 */
export interface ApiError {
  message: string
  errors?: ValidationErrors
}

/** Single-resource success envelope: `{ "data": ... }`. */
export interface Resource<T> {
  data: T
}

/** Pagination metadata as returned by the backend list envelope. */
export interface PaginationMeta {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

/** Paginated list envelope: `{ "data": [...], "meta": {...} }`. */
export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
}
