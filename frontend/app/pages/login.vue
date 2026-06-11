<script setup lang="ts">
import type { FormError, FormSubmitEvent } from '@nuxt/ui'
import type { ApiError } from '~/types/api'

definePageMeta({ layout: 'auth' })

interface LoginState {
  email: string
  password: string
}

const { login } = useAuth()

const state = reactive<LoginState>({ email: '', password: '' })
const serverError = ref<string | null>(null)
const loading = ref(false)
const form = useTemplateRef('form')

function validate(state: LoginState): FormError[] {
  const errors: FormError[] = []
  if (!state.email) errors.push({ name: 'email', message: 'Email is required.' })
  if (!state.password) errors.push({ name: 'password', message: 'Password is required.' })
  return errors
}

async function onSubmit(event: FormSubmitEvent<LoginState>) {
  serverError.value = null
  loading.value = true

  try {
    await login(event.data.email, event.data.password)
    await navigateTo('/')
  } catch (error) {
    const apiError = error as ApiError
    serverError.value = apiError.message

    if (apiError.errors) {
      form.value?.setErrors(
        Object.entries(apiError.errors).flatMap(([name, messages]) =>
          messages.map((message) => ({ name, message })),
        ),
      )
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <UCard class="w-full max-w-sm">
    <template #header>
      <div class="text-center">
        <h1 class="text-xl font-semibold text-default">Racha</h1>
        <p class="mt-1 text-sm text-muted">Sign in to your account</p>
      </div>
    </template>

    <UForm ref="form" :validate="validate" :state="state" class="space-y-4" @submit="onSubmit">
      <UAlert
        v-if="serverError"
        color="error"
        variant="subtle"
        icon="i-lucide-circle-alert"
        :title="serverError"
      />

      <UFormField name="email" label="Email">
        <UInput
          v-model="state.email"
          type="email"
          placeholder="you@example.com"
          autocomplete="email"
          class="w-full"
        />
      </UFormField>

      <UFormField name="password" label="Password">
        <UInput
          v-model="state.password"
          type="password"
          autocomplete="current-password"
          class="w-full"
        />
      </UFormField>

      <UButton type="submit" label="Sign in" block :loading="loading" />
    </UForm>
  </UCard>
</template>
