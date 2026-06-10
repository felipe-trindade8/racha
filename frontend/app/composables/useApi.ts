/**
 * Typed `$fetch`-based API client.
 *
 * Resolves the base URL from runtimeConfig (browser uses the public host, SSR
 * uses the internal Docker hostname — see docs/architecture.md), sets JSON
 * headers, exposes an auth-header injection hook and normalizes every failure
 * to the standard `{ message, errors }` envelope for consumers.
 */
import type { ApiError, ValidationErrors } from '~/types/api'

/** A value that may carry the backend error envelope (`FetchError.data`). */
interface ErrorLike {
  data?: { message?: unknown; errors?: unknown }
  message?: unknown
}

function isValidationErrors(value: unknown): value is ValidationErrors {
  return typeof value === 'object' && value !== null && !Array.isArray(value)
}

/**
 * Coerce any thrown value into the standard `{ message, errors }` shape.
 *
 * Order of preference for the message: the backend envelope `data.message`,
 * then the raw error `message`, then a generic fallback. `errors` is only
 * included when the backend returned field-level validation errors.
 */
export function normalizeApiError(error: unknown): ApiError {
  const source = (error ?? {}) as ErrorLike
  const envelope = source.data

  const message =
    (typeof envelope?.message === 'string' && envelope.message) ||
    (typeof source.message === 'string' && source.message) ||
    'Unexpected error. Please try again.'

  const normalized: ApiError = { message }

  if (isValidationErrors(envelope?.errors)) {
    normalized.errors = envelope.errors
  }

  return normalized
}

export function useApi() {
  const config = useRuntimeConfig()
  const baseURL = import.meta.server ? config.apiBaseInternal : config.public.apiBase

  const client = $fetch.create({
    baseURL,
    headers: {
      Accept: 'application/json',
    },
    onRequest() {
      // Auth-header injection hook. The bearer token is wired in a later issue;
      // once available it is set here, e.g.:
      //   const token = useAuthToken()
      //   if (token.value) options.headers.set('Authorization', `Bearer ${token.value}`)
    },
    onResponseError({ response }) {
      throw normalizeApiError({ data: response._data, message: response.statusText })
    },
  })

  return { client }
}

/** The configured fetch client returned by {@link useApi}. */
export type ApiClient = ReturnType<typeof useApi>['client']
