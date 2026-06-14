<script setup lang="ts">
/**
 * Create a player (administrator-only).
 *
 * Thin page that orchestrates the shared {@link PlayerForm} and the
 * {@link usePlayers} create mutation, surfacing server errors from the standard
 * envelope and returning to the roster list on success.
 */
import type { ApiError, ValidationErrors } from '~/types/api'
import type { PlayerPayload } from '~/types/player'

definePageMeta({ middleware: 'admin' })

const { create, loading } = usePlayers()

const serverError = ref<string | null>(null)
const validationErrors = ref<ValidationErrors | null>(null)

async function onSubmit(payload: PlayerPayload) {
  serverError.value = null
  validationErrors.value = null

  try {
    await create(payload)
    await navigateTo('/admin/players')
  } catch (error) {
    const apiError = error as ApiError
    serverError.value = apiError.message
    validationErrors.value = apiError.errors ?? null
  }
}
</script>

<template>
  <div class="mx-auto flex max-w-2xl flex-col gap-4">
    <div class="flex items-center gap-3">
      <UButton
        to="/admin/players"
        icon="i-lucide-arrow-left"
        color="neutral"
        variant="ghost"
        aria-label="Back to players"
      />
      <h1 class="text-2xl font-semibold text-default">New player</h1>
    </div>

    <PlayerForm
      submit-label="Create player"
      :submitting="loading"
      :server-error="serverError"
      :validation-errors="validationErrors"
      @submit="onSubmit"
    />
  </div>
</template>
