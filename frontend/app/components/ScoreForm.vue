<script setup lang="ts">
/**
 * Record-score form (administrator-only).
 *
 * Builds the score payload for a planned match: each team's result plus optional
 * per-player ratings (1–5). Results map by position — team A is `teams[0]`, team
 * B is `teams[1]` — matching how the list renders and how the match was created.
 * Ratings are keyed by the rostered player's `team_player_id` and only the
 * players actually given a rating are sent.
 *
 * The component owns client-side validation (both results required) and surfaces
 * server errors from the standard `{ message, errors }` envelope: the alert shows
 * the message (including the finished-match lock, which has no field) and field
 * errors are mapped onto the matching results.
 */
import type { FormError, FormSubmitEvent } from '@nuxt/ui'
import type { ValidationErrors } from '~/types/api'
import type {
  MatchScorePayload,
  MatchType,
  PlayerRatingPayload,
  TeamPlayerType,
} from '~/types/match'

const props = withDefaults(
  defineProps<{
    match: MatchType
    submitting?: boolean
    submitLabel?: string
    serverError?: string | null
    validationErrors?: ValidationErrors | null
  }>(),
  {
    submitting: false,
    submitLabel: 'Finish match',
    serverError: null,
    validationErrors: null,
  },
)

const emit = defineEmits<{ submit: [payload: MatchScorePayload] }>()

interface FormState {
  // Indexed by team position: [team A, team B].
  results: [string, string]
}

const state = reactive<FormState>({
  results: [props.match.teams[0]?.result ?? '', props.match.teams[1]?.result ?? ''],
})

// Ratings keyed by team_player_id; pre-seeded for every rostered player (from
// any existing rating) so the reactive object has all keys up front.
const ratings = reactive<Record<number, number | undefined>>({})
for (const team of props.match.teams) {
  for (const rosterEntry of team.players ?? []) {
    ratings[rosterEntry.id] = rosterEntry.gameRating ?? undefined
  }
}

const form = useTemplateRef('form')

const ratingItems = [1, 2, 3, 4, 5].map((value) => ({ label: String(value), value }))

/** The validation field name for a team's result, by position. */
function resultField(teamIndex: number) {
  return teamIndex === 0 ? 'team_a_result' : 'team_b_result'
}

/** Display name for a rostered player. */
function rosterName(rosterEntry: TeamPlayerType) {
  return rosterEntry.player?.nickname ?? rosterEntry.player?.name ?? `#${rosterEntry.playerId}`
}

function validate(state: FormState): FormError[] {
  const errors: FormError[] = []
  state.results.forEach((result, index) => {
    if (!result.trim()) errors.push({ name: resultField(index), message: 'Result is required.' })
  })
  return errors
}

// Surface server-side field errors from the standard envelope onto the form.
watch(
  () => props.validationErrors,
  (errors) => {
    if (!errors) return
    form.value?.setErrors(
      Object.entries(errors).flatMap(([name, messages]) =>
        messages.map((message) => ({ name, message })),
      ),
    )
  },
)

function onSubmit(event: FormSubmitEvent<FormState>) {
  const data = event.data

  const playerRatings = Object.entries(ratings)
    .filter(([, rating]) => rating !== undefined)
    .map<PlayerRatingPayload>(([teamPlayerId, rating]) => ({
      team_player_id: Number(teamPlayerId),
      game_rating: rating as number,
    }))

  const payload: MatchScorePayload = {
    team_a_result: data.results[0].trim(),
    team_b_result: data.results[1].trim(),
  }

  if (playerRatings.length > 0) payload.player_ratings = playerRatings

  emit('submit', payload)
}
</script>

<template>
  <UForm
    ref="form"
    :validate="validate"
    :state="state"
    class="flex flex-col gap-6"
    @submit="onSubmit"
  >
    <UAlert
      v-if="serverError"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="serverError"
    />

    <div class="flex flex-col gap-6 lg:flex-row">
      <UCard v-for="(team, teamIndex) in match.teams" :key="team.id" class="flex-1">
        <template #header>
          <p class="font-medium text-default">{{ team.teamName }}</p>
        </template>

        <div class="flex flex-col gap-4">
          <UFormField :name="resultField(teamIndex)" label="Result">
            <UInput
              v-model="state.results[teamIndex]"
              placeholder="e.g. 3"
              class="w-full sm:w-32"
            />
          </UFormField>

          <div class="flex flex-col gap-2">
            <p class="text-sm font-medium text-default">Player ratings</p>

            <p v-if="(team.players?.length ?? 0) === 0" class="text-sm text-muted">
              No players rostered.
            </p>

            <ul v-else class="flex flex-col gap-2">
              <li
                v-for="rosterEntry in team.players"
                :key="rosterEntry.id"
                class="flex items-center justify-between gap-3"
              >
                <span class="min-w-0 truncate text-sm text-default">{{
                  rosterName(rosterEntry)
                }}</span>
                <USelect
                  v-model="ratings[rosterEntry.id]"
                  :items="ratingItems"
                  value-key="value"
                  placeholder="–"
                  class="w-24 shrink-0"
                  :aria-label="`Rating for ${rosterName(rosterEntry)}`"
                />
              </li>
            </ul>
          </div>
        </div>
      </UCard>
    </div>

    <UButton type="submit" :label="submitLabel" :loading="submitting" class="w-fit" />
  </UForm>
</template>
