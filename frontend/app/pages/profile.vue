<script setup lang="ts">
/**
 * Edit your own player profile.
 *
 * Available to any authenticated user. Loads the player linked to the current
 * user (`user.player_id`) and renders the shared {@link PlayerForm} in
 * `restricted` mode, so only own-profile fields are editable — the backend
 * PlayerPolicy enforces the same rule. Users without a linked player see a
 * message instead.
 */
import type { ApiError, ValidationErrors } from '~/types/api'
import type { Player, PlayerPayload } from '~/types/player'

const { user } = useAuth()
const { fetchOne, update, loading } = usePlayers()

const player = ref<Player | null>(null)
const loadError = ref<string | null>(null)
const serverError = ref<string | null>(null)
const validationErrors = ref<ValidationErrors | null>(null)
const saved = ref(false)

const playerId = computed(() => user.value?.player_id ?? null)

if (playerId.value !== null) {
  try {
    player.value = await fetchOne(playerId.value)
  } catch (error) {
    loadError.value = (error as ApiError).message
  }
}

async function onSubmit(payload: PlayerPayload) {
  if (playerId.value === null) return

  serverError.value = null
  validationErrors.value = null
  saved.value = false

  try {
    player.value = await update(playerId.value, payload)
    saved.value = true
  } catch (error) {
    const apiError = error as ApiError
    serverError.value = apiError.message
    validationErrors.value = apiError.errors ?? null
  }
}
</script>

<template>
  <div class="mx-auto flex max-w-2xl flex-col gap-4">
    <h1 class="text-2xl font-semibold text-default">My profile</h1>

    <UAlert
      v-if="playerId === null"
      color="neutral"
      variant="subtle"
      icon="i-lucide-info"
      title="Your account is not linked to a player yet."
    />

    <UAlert
      v-else-if="loadError"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="loadError"
    />

    <template v-else-if="player">
      <UAlert
        v-if="saved"
        color="success"
        variant="subtle"
        icon="i-lucide-circle-check"
        title="Profile updated."
      />

      <PlayerForm
        :initial-values="player"
        restricted
        submit-label="Save changes"
        :submitting="loading"
        :server-error="serverError"
        :validation-errors="validationErrors"
        @submit="onSubmit"
      />
    </template>
  </div>
</template>
