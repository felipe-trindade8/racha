<script setup lang="ts">
definePageMeta({ middleware: 'admin' })
</script>

<template>
  <div class="mx-auto flex max-w-md flex-col gap-4">
    <h1 class="text-2xl font-semibold text-default">Admin area</h1>
    <p class="text-muted">Administrator-only settings will live here.</p>
    <UButton
      to="/admin/players"
      color="primary"
      variant="subtle"
      icon="i-lucide-users"
      label="Players"
      class="w-fit"
    />
  </div>
</template>
