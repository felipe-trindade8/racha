<script setup lang="ts">
/**
 * Monthly payment generation form.
 *
 * A small form rendered inside the finances page modal to generate the monthly
 * payment charges for active players. The backend derives the billed month from
 * the given date and is idempotent per player/month, so re-running a month is
 * safe. The component owns client-side validation and surfaces server errors
 * from the standard `{ message, errors }` envelope.
 */
import type { FormError, FormSubmitEvent } from '@nuxt/ui'
import type { ValidationErrors } from '~/types/api'
import type { MonthlyPaymentPayload } from '~/types/finance'

const props = withDefaults(
  defineProps<{
    submitting?: boolean
    submitLabel?: string
    serverError?: string | null
    validationErrors?: ValidationErrors | null
  }>(),
  {
    submitting: false,
    submitLabel: 'Generate',
    serverError: null,
    validationErrors: null,
  },
)

const emit = defineEmits<{ submit: [payload: MonthlyPaymentPayload] }>()

interface FormState {
  date: string
  amount: number | undefined
}

function today(): string {
  return new Date().toISOString().slice(0, 10)
}

const state = reactive<FormState>({
  date: today(),
  amount: undefined,
})

const form = useTemplateRef('form')

function validate(state: FormState): FormError[] {
  const errors: FormError[] = []
  if (!state.date) errors.push({ name: 'date', message: 'Date is required.' })
  if (state.amount === undefined || state.amount <= 0) {
    errors.push({ name: 'amount', message: 'Amount must be greater than zero.' })
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
  emit('submit', {
    date: event.data.date,
    amount: event.data.amount ?? 0,
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

    <p class="text-sm text-muted">
      Generates one open income charge per active player for the date's month. Re-running the same
      month does not create duplicates.
    </p>

    <div class="flex flex-col gap-4 sm:flex-row">
      <UFormField name="date" label="Date" class="w-full">
        <UInput v-model="state.date" type="date" class="w-full" />
      </UFormField>

      <UFormField name="amount" label="Amount per player" class="w-full">
        <UInput
          v-model.number="state.amount"
          type="number"
          step="0.01"
          min="0"
          placeholder="0.00"
          class="w-full"
        />
      </UFormField>
    </div>

    <UButton type="submit" :label="submitLabel" :loading="submitting" class="w-fit" />
  </UForm>
</template>
