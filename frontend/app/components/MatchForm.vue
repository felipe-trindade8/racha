<script setup lang="ts">
/**
 * Match create form (administrator-only).
 *
 * Builds the create payload for a match: a date plus exactly two teams, each
 * with a name and a roster of players (position optional, starter flag). The
 * roster picker reuses the player list ({@link usePlayers}) and the position
 * reference data ({@link usePositions}, as in {@link PlayerForm}). Only active
 * players are offered.
 *
 * The duplicate-player rule is enforced client-side by excluding any player
 * already rostered — on either team — from both "add player" pickers, so a
 * player can never be assigned twice. The backend re-checks this and surfaces a
 * 422 from GameMatchService; that, and any other server error from the standard
 * `{ message, errors }` envelope, is surfaced via the alert and mapped onto the
 * matching fields.
 */
import type { FormError, FormSubmitEvent } from '@nuxt/ui'
import type { ValidationErrors } from '~/types/api'
import type { MatchPayload, MatchTeamPayload } from '~/types/match'
import { PlayerStatus } from '~/types/player'

const props = withDefaults(
  defineProps<{
    submitting?: boolean
    submitLabel?: string
    serverError?: string | null
    validationErrors?: ValidationErrors | null
  }>(),
  {
    submitting: false,
    submitLabel: 'Create match',
    serverError: null,
    validationErrors: null,
  },
)

const emit = defineEmits<{ submit: [payload: MatchPayload] }>()

interface RosterEntry {
  playerId: number
  positionId: number | undefined
  isStarter: boolean
}

interface TeamState {
  teamName: string
  roster: RosterEntry[]
}

interface FormState {
  date: string
  teams: [TeamState, TeamState]
}

const { players, fetchList } = usePlayers()
const { positions, ensureLoaded } = usePositions()

// Load the roster picker's reference data up front (SSR-friendly, like
// PlayerForm). Only active players can be rostered.
await Promise.all([fetchList({ status: PlayerStatus.Active, per_page: 100 }), ensureLoaded()])

const state = reactive<FormState>({
  date: '',
  teams: [
    { teamName: '', roster: [] },
    { teamName: '', roster: [] },
  ],
})

// The select bound to each team's "add player" control; reset to undefined
// after a pick so it stays a placeholder.
const playerToAdd = reactive<[number | undefined, number | undefined]>([undefined, undefined])

const form = useTemplateRef('form')

const positionItems = computed(() =>
  positions.value.map((position) => ({
    label: `${position.code} — ${position.name}`,
    value: position.id,
  })),
)

/** Ids already rostered on either team — excluded from both pickers. */
const rosteredIds = computed(
  () => new Set(state.teams.flatMap((team) => team.roster.map((entry) => entry.playerId))),
)

/** Players still available to add (active, not yet rostered on any team). */
const availableItems = computed(() =>
  players.value
    .filter((player) => !rosteredIds.value.has(player.id))
    .map((player) => ({ label: player.nickname ?? player.name, value: player.id })),
)

/** Display name for a rostered player id. */
function playerName(id: number) {
  const player = players.value.find((candidate) => candidate.id === id)
  return player?.nickname ?? player?.name ?? `#${id}`
}

function addPlayer(teamIndex: number, playerId: number | undefined) {
  if (playerId === undefined) return
  state.teams[teamIndex]!.roster.push({ playerId, positionId: undefined, isStarter: false })
  playerToAdd[teamIndex] = undefined
}

function removePlayer(teamIndex: number, rosterIndex: number) {
  state.teams[teamIndex]!.roster.splice(rosterIndex, 1)
}

function validate(state: FormState): FormError[] {
  const errors: FormError[] = []
  if (!state.date) errors.push({ name: 'date', message: 'Date is required.' })
  state.teams.forEach((team, index) => {
    if (!team.teamName.trim()) {
      errors.push({ name: `teams.${index}.team_name`, message: 'Team name is required.' })
    }
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

  const teams = data.teams.map<MatchTeamPayload>((team) => ({
    team_name: team.teamName.trim(),
    players: team.roster.map((entry) => ({
      player_id: entry.playerId,
      position_id: entry.positionId ?? null,
      is_starter: entry.isStarter,
    })),
  }))

  emit('submit', { date: data.date, teams })
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

    <UFormField name="date" label="Date">
      <UInput v-model="state.date" type="date" class="w-full sm:w-48" />
    </UFormField>

    <div class="flex flex-col gap-6 lg:flex-row">
      <UCard v-for="(team, teamIndex) in state.teams" :key="teamIndex" class="flex-1">
        <template #header>
          <UFormField :name="`teams.${teamIndex}.team_name`" :label="`Team ${teamIndex + 1} name`">
            <UInput v-model="team.teamName" :placeholder="`Team ${teamIndex + 1}`" class="w-full" />
          </UFormField>
        </template>

        <div class="flex flex-col gap-3">
          <p class="text-sm font-medium text-default">Roster</p>

          <p v-if="team.roster.length === 0" class="text-sm text-muted">No players assigned yet.</p>

          <ul v-else class="flex flex-col gap-3">
            <li
              v-for="(entry, rosterIndex) in team.roster"
              :key="entry.playerId"
              class="flex flex-col gap-2 rounded-md border border-default p-3"
            >
              <div class="flex items-center justify-between gap-2">
                <span class="truncate font-medium text-default">
                  {{ playerName(entry.playerId) }}
                </span>
                <UButton
                  icon="i-lucide-x"
                  color="neutral"
                  variant="ghost"
                  size="xs"
                  :aria-label="`Remove ${playerName(entry.playerId)}`"
                  @click="removePlayer(teamIndex, rosterIndex)"
                />
              </div>
              <div class="flex flex-wrap items-center gap-3">
                <USelect
                  v-model="entry.positionId"
                  :items="positionItems"
                  value-key="value"
                  placeholder="Position"
                  class="w-full sm:w-48"
                  :aria-label="`Position for ${playerName(entry.playerId)}`"
                />
                <USwitch
                  v-model="entry.isStarter"
                  label="Starter"
                  :aria-label="`Starter for ${playerName(entry.playerId)}`"
                />
              </div>
            </li>
          </ul>

          <USelectMenu
            v-model="playerToAdd[teamIndex]"
            :items="availableItems"
            value-key="value"
            icon="i-lucide-user-plus"
            placeholder="Add player"
            :aria-label="`Add player to team ${teamIndex + 1}`"
            class="w-full"
            @update:model-value="(value) => addPlayer(teamIndex, value as number | undefined)"
          />
        </div>
      </UCard>
    </div>

    <UButton type="submit" :label="submitLabel" :loading="submitting" class="w-fit" />
  </UForm>
</template>
