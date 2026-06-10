import { describe, it, expect } from 'vitest'
import { normalizeApiError } from './useApi'

describe('normalizeApiError', () => {
  it('passes through the backend error envelope', () => {
    const error = { data: { message: 'Not found' } }

    expect(normalizeApiError(error)).toEqual({ message: 'Not found' })
  })

  it('preserves field-level validation errors', () => {
    const error = {
      data: {
        message: 'Validation failed',
        errors: { email: ['The email field is required.'] },
      },
    }

    expect(normalizeApiError(error)).toEqual({
      message: 'Validation failed',
      errors: { email: ['The email field is required.'] },
    })
  })

  it('falls back to the raw error message when there is no envelope', () => {
    const error = { message: 'Network Error' }

    expect(normalizeApiError(error)).toEqual({ message: 'Network Error' })
  })

  it('falls back to a generic message for unknown errors', () => {
    expect(normalizeApiError(undefined)).toEqual({
      message: 'Unexpected error. Please try again.',
    })
    expect(normalizeApiError({})).toEqual({
      message: 'Unexpected error. Please try again.',
    })
  })

  it('ignores a malformed errors field', () => {
    const error = { data: { message: 'Oops', errors: 'not-an-object' } }

    expect(normalizeApiError(error)).toEqual({ message: 'Oops' })
  })
})
