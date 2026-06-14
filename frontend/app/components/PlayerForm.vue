<script setup lang="ts">
/**
 * Player create/edit form.
 *
 * A single reusable form shared by the admin create/edit pages and the player
 * self-edit profile page. In `restricted` mode (a player editing their own
 * record) the rating and status fields are hidden, so players can only edit
 * their own profile fields; the backend enforces the same rule via PlayerPolicy.
 *
 * The component owns client-side validation and surfaces server errors from the
 * standard `{ message, errors }` envelope: `serverError` renders an alert and
 * `validationErrors` are mapped onto the matching fields.
 */
import type { FormError, FormSubmitEvent } from '@nuxt/ui'
import type { ValidationErrors } from '~/types/api'
import { PlayerStatus, type Player, type PlayerPayload } from '~/types/player'

const props = withDefaults(
  defineProps<{
    initialValues?: Player | null
    restricted?: boolean
    submitting?: boolean
    submitLabel?: string
    serverError?: string | null
    validationErrors?: ValidationErrors | null
  }>(),
  {
    initialValues: null,
    restricted: false,
    submitting: false,
    submitLabel: 'Save',
    serverError: null,
    validationErrors: null,
  },
)

const emit = defineEmits<{ submit: [payload: PlayerPayload] }>()

interface FormState {
  name: string
  nickname: string
  rating: number | undefined
  status: PlayerStatus
  phone: string
  positionIds: number[]
}

const { positions, ensureLoaded } = usePositions()
await ensureLoaded()

const state = reactive<FormState>({
  name: props.initialValues?.name ?? '',
  nickname: props.initialValues?.nickname ?? '',
  rating: props.initialValues?.rating,
  status: props.initialValues?.status ?? PlayerStatus.Active,
  phone: props.initialValues?.phone ?? '',
  positionIds: props.initialValues?.positions.map((position) => position.id) ?? [],
})

const form = useTemplateRef('form')

const ratingItems = [1, 2, 3, 4, 5].map((value) => ({ label: String(value), value }))

const statusItems = [
  { label: 'Active', value: PlayerStatus.Active },
  { label: 'Injured', value: PlayerStatus.Injured },
  { label: 'Inactive', value: PlayerStatus.Inactive },
]

const positionItems = computed(() =>
  positions.value.map((position) => ({
    label: `${position.code} — ${position.name}`,
    value: position.id,
  })),
)

function validate(state: FormState): FormError[] {
  const errors: FormError[] = []
  if (!state.name.trim()) errors.push({ name: 'name', message: 'Name is required.' })
  if (!props.restricted && (state.rating === undefined || state.rating < 1 || state.rating > 5)) {
    errors.push({ name: 'rating', message: 'Rating is required.' })
  }
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

  const payload: PlayerPayload = {
    name: data.name.trim(),
    nickname: data.nickname.trim() || null,
    phone: data.phone.trim() || null,
    position_ids: data.positionIds,
    // Players cannot change their own rating; admins always set it (validated
    // present above), so the fallback only guards the type.
    rating: data.rating ?? 1,
  }

  if (props.restricted) {
    // Players may not change their own rating or status; omit both so a partial
    // update never touches them.
    delete (payload as Partial<PlayerPayload>).rating
  } else {
    payload.status = data.status
  }

  emit('submit', payload)
}
</script>

<template>
  <UForm
    ref="form"
    :validate="validate"
    :state="state"
    class="flex flex-col gap-4"
    @submit="onSubmit"
  >
    <UAlert
      v-if="serverError"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="serverError"
    />

    <UFormField name="name" label="Name">
      <UInput v-model="state.name" placeholder="Full name" class="w-full" />
    </UFormField>

    <UFormField name="nickname" label="Nickname">
      <UInput v-model="state.nickname" placeholder="Optional" class="w-full" />
    </UFormField>

    <UFormField v-if="!restricted" name="rating" label="Rating">
      <USelect
        v-model="state.rating"
        :items="ratingItems"
        placeholder="1–5"
        class="w-full sm:w-32"
      />
    </UFormField>

    <UFormField v-if="!restricted" name="status" label="Status">
      <USelect v-model="state.status" :items="statusItems" class="w-full sm:w-48" />
    </UFormField>

    <UFormField name="position_ids" label="Positions">
      <USelectMenu
        v-model="state.positionIds"
        :items="positionItems"
        value-key="value"
        multiple
        placeholder="Select positions"
        class="w-full"
      />
    </UFormField>

    <UFormField name="phone" label="Phone">
      <UInput v-model="state.phone" placeholder="Optional" class="w-full" />
    </UFormField>

    <UButton type="submit" :label="submitLabel" :loading="submitting" class="w-fit" />
  </UForm>
</template>
