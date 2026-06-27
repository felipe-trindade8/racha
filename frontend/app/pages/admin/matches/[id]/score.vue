<script setup lang="ts">
/**
 * Record a match's score (administrator-only).
 *
 * Loads the match by route id and, while it is planned, orchestrates the shared
 * {@link ScoreForm} and the {@link useMatches} score mutation. On success the
 * returned (finished) match replaces the local one, so the UI reflects the
 * finished state in place; an already-finished match is shown read-only, since
 * the backend locks it until it is reopened. Server errors from the standard
 * envelope are surfaced.
 */
import type { ApiError, ValidationErrors } from '~/types/api'
import { MatchStatus, type MatchScorePayload, type MatchType } from '~/types/match'

definePageMeta({ middleware: 'admin' })

const route = useRoute()
const id = Number(route.params.id)

const { fetchOne, score, loading } = useMatches()

const match = ref<MatchType | null>(null)
const loadError = ref<string | null>(null)
const serverError = ref<string | null>(null)
const validationErrors = ref<ValidationErrors | null>(null)

try {
  match.value = await fetchOne(id)
} catch (error) {
  loadError.value = (error as ApiError).message
}

const isFinished = computed(() => match.value?.status === MatchStatus.Finished)

/** A team's result by position, falling back to a dash. */
function teamResult(side: number) {
  return match.value?.teams[side]?.result ?? '–'
}

async function onSubmit(payload: MatchScorePayload) {
  serverError.value = null
  validationErrors.value = null

  try {
    // Replace the local match with the finished one so the page reflects the
    // settled result without another fetch.
    match.value = await score(id, payload)
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
      <h1 class="text-2xl font-semibold text-default">Record score</h1>
    </div>

    <UAlert
      v-if="loadError"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="loadError"
    />

    <template v-else-if="match">
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="truncate font-medium text-default">
            {{ match.teams[0]?.teamName ?? 'TBD' }} vs {{ match.teams[1]?.teamName ?? 'TBD' }}
          </p>
          <p class="text-sm text-muted">{{ match.date }}</p>
        </div>
        <UBadge :color="isFinished ? 'success' : 'info'" variant="subtle">{{
          match.status
        }}</UBadge>
      </div>

      <!-- Finished: locked, show the settled result read-only. -->
      <UCard v-if="isFinished">
        <div class="flex items-center justify-center gap-3 text-lg font-semibold text-default">
          <span class="truncate">{{ match.teams[0]?.teamName }}</span>
          <span class="tabular-nums">{{ teamResult(0) }} – {{ teamResult(1) }}</span>
          <span class="truncate">{{ match.teams[1]?.teamName }}</span>
        </div>
        <p class="mt-2 text-center text-sm text-muted">
          This match is finished. Reopen it to change the score.
        </p>
      </UCard>

      <!-- Planned: enter the score. -->
      <ScoreForm
        v-else
        :match="match"
        :submitting="loading"
        :server-error="serverError"
        :validation-errors="validationErrors"
        @submit="onSubmit"
      />
    </template>
  </div>
</template>
