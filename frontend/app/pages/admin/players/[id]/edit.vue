<script setup lang="ts">
/**
 * Edit a player (administrator-only).
 *
 * Loads the player by route id, then orchestrates the shared {@link PlayerForm}
 * and the {@link usePlayers} update mutation, surfacing server errors from the
 * standard envelope and returning to the roster list on success.
 */
import type { ApiError, ValidationErrors } from '~/types/api'
import type { Player, PlayerPayload } from '~/types/player'

definePageMeta({ middleware: 'admin' })

const route = useRoute()
const id = Number(route.params.id)

const { fetchOne, update, loading } = usePlayers()

const player = ref<Player | null>(null)
const loadError = ref<string | null>(null)
const serverError = ref<string | null>(null)
const validationErrors = ref<ValidationErrors | null>(null)

try {
  player.value = await fetchOne(id)
} catch (error) {
  loadError.value = (error as ApiError).message
}

async function onSubmit(payload: PlayerPayload) {
  serverError.value = null
  validationErrors.value = null

  try {
    await update(id, payload)
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
      <h1 class="text-2xl font-semibold text-default">Edit player</h1>
    </div>

    <UAlert
      v-if="loadError"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="loadError"
    />

    <PlayerForm
      v-else-if="player"
      :initial-values="player"
      submit-label="Save changes"
      :submitting="loading"
      :server-error="serverError"
      :validation-errors="validationErrors"
      @submit="onSubmit"
    />
  </div>
</template>
