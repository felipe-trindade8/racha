<script setup lang="ts">
const { user, logout } = useAuth()

async function onLogout() {
  await logout()
  await navigateTo('/login')
}
</script>

<template>
  <div class="flex min-h-dvh flex-col">
    <header class="flex items-center justify-between border-b border-default px-4 py-3">
      <NuxtLink to="/" class="text-lg font-semibold text-default">Racha</NuxtLink>
      <div class="flex items-center gap-3">
        <span v-if="user" class="text-sm text-muted">{{ user.name }}</span>
        <UButton
          color="neutral"
          variant="ghost"
          icon="i-lucide-log-out"
          label="Logout"
          @click="onLogout"
        />
      </div>
    </header>
    <main class="flex-1 p-4">
      <slot />
    </main>
  </div>
</template>
