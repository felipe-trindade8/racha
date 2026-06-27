<script setup lang="ts">
/**
 * Create a match (administrator-only).
 *
 * Thin page that orchestrates the shared {@link MatchForm} and the
 * {@link useMatches} create mutation, surfacing server errors from the standard
 * envelope and returning to the matches list on success.
 */
import type { ApiError, ValidationErrors } from '~/types/api'
import type { MatchPayload } from '~/types/match'

definePageMeta({ middleware: 'admin' })

const { create, loading } = useMatches()

const serverError = ref<string | null>(null)
const validationErrors = ref<ValidationErrors | null>(null)

async function onSubmit(payload: MatchPayload) {
  serverError.value = null
  validationErrors.value = null

  try {
    await create(payload)
    await navigateTo('/matches')
  } catch (error) {
    const apiError = error as ApiError
    serverError.value = apiError.message
    validationErrors.value = apiError.errors ?? null
  }
}
</script>

<template>
  <div class="mx-auto flex max-w-4xl flex-col gap-4">
    <div class="flex items-center gap-3">
      <UButton
        to="/matches"
        icon="i-lucide-arrow-left"
        color="neutral"
        variant="ghost"
        aria-label="Back to matches"
      />
      <h1 class="text-2xl font-semibold text-default">New match</h1>
    </div>

    <MatchForm
      :submitting="loading"
      :server-error="serverError"
      :validation-errors="validationErrors"
      @submit="onSubmit"
    />
  </div>
</template>
