<script setup lang="ts">
/**
 * Player roster list (administrator-only).
 *
 * Mobile-first list of players with debounced search, a status filter and
 * server-side pagination, driven by {@link usePlayers}. Listing is an
 * administrator-only action on the backend (PlayerPolicy::viewAny), so the page
 * is gated by the `admin` middleware; the create action is additionally guarded
 * by `isAdmin` for clarity.
 */
import { PlayerStatus, type PlayerQuery } from '~/types/player'

definePageMeta({ middleware: 'admin' })

const PER_PAGE = 15
const SEARCH_DEBOUNCE_MS = 300

const { isAdmin } = useAuth()
const { players, meta, loading, error, fetchList } = usePlayers()

const ALL_STATUSES = 'all'

const searchInput = ref('')
const appliedSearch = ref('')
const statusFilter = ref<string>(ALL_STATUSES)
const page = ref(1)

const statusItems = [
  { label: 'All roster', value: ALL_STATUSES },
  { label: 'Active', value: PlayerStatus.Active },
  { label: 'Injured', value: PlayerStatus.Injured },
  { label: 'Inactive', value: PlayerStatus.Inactive },
]

const statusBadgeColor: Record<PlayerStatus, 'success' | 'warning' | 'neutral'> = {
  [PlayerStatus.Active]: 'success',
  [PlayerStatus.Injured]: 'warning',
  [PlayerStatus.Inactive]: 'neutral',
}

const query = computed<PlayerQuery>(() => ({
  search: appliedSearch.value || undefined,
  status: statusFilter.value === ALL_STATUSES ? undefined : (statusFilter.value as PlayerStatus),
  per_page: PER_PAGE,
  page: page.value,
}))

const hasResults = computed(() => players.value.length > 0)

/** Refetch the current page; errors are surfaced through the composable's `error`. */
async function load() {
  try {
    await fetchList(query.value)
  } catch {
    // The error is captured in `error` by usePlayers and rendered below.
  }
}

// Initial server-side render load, then refetch whenever the query changes.
await load()
watch(query, load)

// Debounce the search box and reset to the first page on a new term.
let searchTimer: ReturnType<typeof setTimeout> | undefined
watch(searchInput, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    appliedSearch.value = value.trim()
    page.value = 1
  }, SEARCH_DEBOUNCE_MS)
})

// Reset to the first page when the status filter changes.
function onStatusChange(value: string) {
  statusFilter.value = value
  page.value = 1
}
</script>

<template>
  <div class="mx-auto flex max-w-2xl flex-col gap-4">
    <div class="flex items-center justify-between gap-3">
      <h1 class="text-2xl font-semibold text-default">Players</h1>
      <UButton
        v-if="isAdmin"
        icon="i-lucide-plus"
        label="New player"
        color="primary"
        disabled
      />
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
      <UInput
        v-model="searchInput"
        icon="i-lucide-search"
        placeholder="Search by name or nickname"
        class="w-full"
      />
      <USelect
        :model-value="statusFilter"
        :items="statusItems"
        class="w-full sm:w-48"
        @update:model-value="onStatusChange"
      />
    </div>

    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="error.message"
    />

    <div v-if="loading" class="flex flex-col gap-3">
      <USkeleton v-for="n in 4" :key="n" class="h-20 w-full" />
    </div>

    <p v-else-if="!hasResults" class="py-8 text-center text-muted">No players found.</p>

    <ul v-else class="flex flex-col gap-3">
      <li v-for="player in players" :key="player.id">
        <UCard>
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate font-medium text-default">{{ player.name }}</p>
              <p v-if="player.nickname" class="truncate text-sm text-muted">
                {{ player.nickname }}
              </p>
            </div>
            <UBadge :color="statusBadgeColor[player.status]" variant="subtle" class="shrink-0">
              {{ player.status }}
            </UBadge>
          </div>

          <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="flex items-center gap-1 text-sm text-muted">
              <UIcon name="i-lucide-star" class="text-amber-500" />
              {{ player.rating }}
            </span>
            <UBadge
              v-for="position in player.positions"
              :key="position.id"
              color="neutral"
              variant="soft"
            >
              {{ position.code }}
            </UBadge>
          </div>
        </UCard>
      </li>
    </ul>

    <div v-if="meta && meta.last_page > 1" class="flex justify-center">
      <UPagination
        v-model:page="page"
        :total="meta.total"
        :items-per-page="PER_PAGE"
      />
    </div>
  </div>
</template>
