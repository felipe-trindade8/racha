<script setup lang="ts">
const { user, isAdmin } = useAuth()
</script>

<template>
  <div class="mx-auto flex max-w-md flex-col gap-4">
    <h1 class="text-2xl font-semibold text-default">Welcome, {{ user?.name }}</h1>
    <p class="text-muted">Weekly soccer, organized.</p>
    <div class="flex flex-wrap gap-3">
      <UButton
        v-if="isAdmin"
        to="/admin"
        color="primary"
        icon="i-lucide-shield"
        label="Admin area"
        class="w-fit"
      />
      <UButton
        v-if="user?.player_id"
        to="/profile"
        color="neutral"
        variant="subtle"
        icon="i-lucide-user"
        label="My profile"
        class="w-fit"
      />
    </div>
  </div>
</template>
