<script setup lang="ts">
const config = useRuntimeConfig()
</script>

<template>
  <main class="min-h-screen flex flex-col items-center justify-center gap-4 p-8">
    <h1 class="text-2xl font-semibold">Racha</h1>
    <p class="text-(--ui-text-muted)">API base: {{ config.public.apiBase }}</p>
    <UButton color="primary" size="lg">It works</UButton>
  </main>
</template>
