<script setup lang="ts">
/**
 * Match list & history (all roles).
 *
 * Mobile-first page with two tabs: a "Planned" list of matches with a
 * status/date filter and server-side pagination, and a "History" tab of
 * finished matches with their results. Both are driven by {@link useMatches};
 * the page is read-only (creating, editing and scoring live elsewhere). Viewing
 * matches is allowed for every role, so the page relies on the global auth guard
 * and adds no `admin` middleware.
 */
import type { TabsItem } from '@nuxt/ui'
import { MatchStatus, type MatchHistoryQuery, type MatchQuery, type MatchType } from '~/types/match'

const PER_PAGE = 15
const ALL = 'all'

const { matches, meta, history, historyMeta, loading, error, fetchList, fetchHistory } =
  useMatches()

const tab = ref<string>('planned')
const tabItems: TabsItem[] = [
  { label: 'Planned', value: 'planned' },
  { label: 'History', value: 'history' },
]

const statusFilter = ref<string>(MatchStatus.Planned)
const dateFilter = ref('')
const page = ref(1)
const historyPage = ref(1)

const statusItems = [
  { label: 'Planned', value: MatchStatus.Planned },
  { label: 'Finished', value: MatchStatus.Finished },
  { label: 'All', value: ALL },
]

const statusBadgeColor: Record<MatchStatus, 'info' | 'success'> = {
  [MatchStatus.Planned]: 'info',
  [MatchStatus.Finished]: 'success',
}

const listQuery = computed<MatchQuery>(() => ({
  status: statusFilter.value === ALL ? undefined : (statusFilter.value as MatchStatus),
  date: dateFilter.value || undefined,
  per_page: PER_PAGE,
  page: page.value,
}))

const historyQuery = computed<MatchHistoryQuery>(() => ({
  per_page: PER_PAGE,
  page: historyPage.value,
}))

const hasMatches = computed(() => matches.value.length > 0)
const hasHistory = computed(() => history.value.length > 0)

/** A match's team at the given side; matches always have two teams. */
function teamName(match: MatchType, side: number) {
  return match.teams[side]?.teamName ?? 'TBD'
}

function teamResult(match: MatchType, side: number) {
  return match.teams[side]?.result ?? '–'
}

/** Refetch the current list page; errors surface through the composable's `error`. */
async function loadList() {
  try {
    await fetchList(listQuery.value)
  } catch {
    // Captured in `error` by useMatches and rendered below.
  }
}

/** Refetch the current history page; errors surface through the composable's `error`. */
async function loadHistory() {
  try {
    await fetchHistory(historyQuery.value)
  } catch {
    // Captured in `error` by useMatches and rendered below.
  }
}

// Load both views up front (server-side on first paint) so switching tabs is
// instant, then refetch each whenever its own query changes.
await Promise.all([loadList(), loadHistory()])
watch(listQuery, loadList)
watch(historyQuery, loadHistory)

// Reset to the first page whenever a list filter changes.
watch([statusFilter, dateFilter], () => {
  page.value = 1
})
</script>

<template>
  <div class="mx-auto flex max-w-2xl flex-col gap-4">
    <h1 class="text-2xl font-semibold text-default">Matches</h1>

    <UTabs v-model="tab" :items="tabItems" :content="false" class="w-full" />

    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="error.message"
    />

    <!-- Planned / list view ------------------------------------------------ -->
    <template v-if="tab === 'planned'">
      <div class="flex flex-col gap-3 sm:flex-row">
        <USelect v-model="statusFilter" :items="statusItems" class="w-full sm:w-40" />
        <UInput v-model="dateFilter" type="date" class="w-full sm:w-44" aria-label="Date" />
      </div>

      <div v-if="loading" class="flex flex-col gap-3">
        <USkeleton v-for="n in 4" :key="n" class="h-20 w-full" />
      </div>

      <p v-else-if="!hasMatches" class="py-8 text-center text-muted">No matches found.</p>

      <ul v-else class="flex flex-col gap-3">
        <li v-for="match in matches" :key="match.id">
          <UCard>
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate font-medium text-default">
                  {{ teamName(match, 0) }} vs {{ teamName(match, 1) }}
                </p>
                <p class="text-sm text-muted">{{ match.date }}</p>
              </div>
              <div class="flex shrink-0 items-center gap-2">
                <span
                  v-if="match.status === MatchStatus.Finished"
                  class="font-semibold tabular-nums text-default"
                >
                  {{ teamResult(match, 0) }} – {{ teamResult(match, 1) }}
                </span>
                <UBadge :color="statusBadgeColor[match.status]" variant="subtle">
                  {{ match.status }}
                </UBadge>
              </div>
            </div>
          </UCard>
        </li>
      </ul>

      <div v-if="meta && meta.last_page > 1" class="flex justify-center">
        <UPagination v-model:page="page" :total="meta.total" :items-per-page="PER_PAGE" />
      </div>
    </template>

    <!-- History view ------------------------------------------------------- -->
    <template v-else>
      <div v-if="loading" class="flex flex-col gap-3">
        <USkeleton v-for="n in 4" :key="n" class="h-20 w-full" />
      </div>

      <p v-else-if="!hasHistory" class="py-8 text-center text-muted">No finished matches yet.</p>

      <ul v-else class="flex flex-col gap-3">
        <li v-for="entry in history" :key="entry.id">
          <UCard>
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate font-medium text-default">
                  {{ entry.teamA.teamName }} vs {{ entry.teamB.teamName }}
                </p>
                <p class="text-sm text-muted">{{ entry.date }}</p>
              </div>
              <span class="shrink-0 font-semibold tabular-nums text-default">
                {{ entry.teamA.result ?? '–' }} – {{ entry.teamB.result ?? '–' }}
              </span>
            </div>
          </UCard>
        </li>
      </ul>

      <div v-if="historyMeta && historyMeta.last_page > 1" class="flex justify-center">
        <UPagination
          v-model:page="historyPage"
          :total="historyMeta.total"
          :items-per-page="PER_PAGE"
        />
      </div>
    </template>
  </div>
</template>
