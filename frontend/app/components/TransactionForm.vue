<script setup lang="ts">
/**
 * Financial transaction create/edit form.
 *
 * A reusable form rendered inside the finances page modal. It owns client-side
 * validation and surfaces server errors from the standard `{ message, errors }`
 * envelope: `serverError` renders an alert and `validationErrors` are mapped
 * onto the matching fields. The optional player select lets a transaction be
 * attached to a roster member or left group-wide (the backend `player_id` is
 * nullable).
 */
import type { FormError, FormSubmitEvent } from '@nuxt/ui'
import type { ValidationErrors } from '~/types/api'
import type { Player } from '~/types/player'
import {
  TransactionType,
  type FinancialTransaction,
  type FinancialTransactionPayload,
} from '~/types/finance'

const props = withDefaults(
  defineProps<{
    initialValues?: FinancialTransaction | null
    players?: Player[]
    submitting?: boolean
    submitLabel?: string
    serverError?: string | null
    validationErrors?: ValidationErrors | null
  }>(),
  {
    initialValues: null,
    players: () => [],
    submitting: false,
    submitLabel: 'Save',
    serverError: null,
    validationErrors: null,
  },
)

const emit = defineEmits<{ submit: [payload: FinancialTransactionPayload] }>()

// Sentinel value for the "no player" (group-wide) option, since the select
// cannot bind a null option value cleanly.
const NO_PLAYER = 0

interface FormState {
  description: string
  amount: number | undefined
  type: TransactionType
  date: string
  playerId: number
}

function today(): string {
  return new Date().toISOString().slice(0, 10)
}

const state = reactive<FormState>({
  description: props.initialValues?.description ?? '',
  amount: props.initialValues ? Number(props.initialValues.amount) : undefined,
  type: props.initialValues?.type ?? TransactionType.Income,
  date: props.initialValues?.date ?? today(),
  playerId: props.initialValues?.playerId ?? NO_PLAYER,
})

const form = useTemplateRef('form')

const typeItems = [
  { label: 'Income', value: TransactionType.Income },
  { label: 'Expense', value: TransactionType.Expense },
]

const playerItems = computed(() => [
  { label: 'No player (group-wide)', value: NO_PLAYER },
  ...props.players.map((player) => ({
    label: player.nickname ? `${player.name} (${player.nickname})` : player.name,
    value: player.id,
  })),
])

function validate(state: FormState): FormError[] {
  const errors: FormError[] = []
  if (!state.description.trim()) {
    errors.push({ name: 'description', message: 'Description is required.' })
  }
  if (state.amount === undefined || state.amount <= 0) {
    errors.push({ name: 'amount', message: 'Amount must be greater than zero.' })
  }
  if (!state.date) errors.push({ name: 'date', message: 'Date is required.' })
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

  emit('submit', {
    description: data.description.trim(),
    amount: data.amount ?? 0,
    type: data.type,
    date: data.date,
    player_id: data.playerId === NO_PLAYER ? null : data.playerId,
  })
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

    <UFormField name="description" label="Description">
      <UInput v-model="state.description" placeholder="e.g. Field rental" class="w-full" />
    </UFormField>

    <div class="flex flex-col gap-4 sm:flex-row">
      <UFormField name="amount" label="Amount" class="w-full">
        <UInput
          v-model.number="state.amount"
          type="number"
          step="0.01"
          min="0"
          placeholder="0.00"
          class="w-full"
        />
      </UFormField>

      <UFormField name="type" label="Type" class="w-full">
        <USelect v-model="state.type" :items="typeItems" class="w-full" />
      </UFormField>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row">
      <UFormField name="date" label="Date" class="w-full">
        <UInput v-model="state.date" type="date" class="w-full" />
      </UFormField>

      <UFormField name="player_id" label="Player" class="w-full">
        <USelect v-model="state.playerId" :items="playerItems" class="w-full" />
      </UFormField>
    </div>

    <UButton type="submit" :label="submitLabel" :loading="submitting" class="w-fit" />
  </UForm>
</template>
