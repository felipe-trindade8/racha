<script setup lang="ts">
/**
 * Cash flow dashboard (administrator-only).
 *
 * Shows the current balance, income/expense totals and a monthly breakdown from
 * the cash-flow endpoint, driven by {@link useFinances}. A status toggle selects
 * the scope (paid = realized cash by default, open = pending, all = both). The
 * cash-flow summary is administrator-only on the backend, so the page is gated
 * by the `admin` middleware.
 */
import { CashFlowStatus } from '~/types/finance'

definePageMeta({ middleware: 'admin' })

const { summary, loading, error, fetchSummary } = useFinances()

const statusFilter = ref<CashFlowStatus>(CashFlowStatus.Paid)

const statusItems = [
  { label: 'Paid', value: CashFlowStatus.Paid },
  { label: 'Open', value: CashFlowStatus.Open },
  { label: 'All', value: CashFlowStatus.All },
]

/** Refetch the summary; errors are surfaced through the composable's `error`. */
async function load() {
  try {
    await fetchSummary({ status: statusFilter.value })
  } catch {
    // The error is captured in `error` by useFinances and rendered below.
  }
}

// Initial server-side render load, then refetch whenever the status changes.
await load()
watch(statusFilter, load)

const isPositive = computed(() => Number(summary.value?.totals.balance ?? 0) >= 0)
const hasMonthly = computed(() => (summary.value?.monthly.length ?? 0) > 0)
</script>

<template>
  <div class="mx-auto flex max-w-2xl flex-col gap-4">
    <div class="flex items-center gap-3">
      <UButton
        to="/admin/finances"
        icon="i-lucide-arrow-left"
        color="neutral"
        variant="ghost"
        aria-label="Back to finances"
      />
      <h1 class="text-2xl font-semibold text-default">Cash flow</h1>
      <USelect v-model="statusFilter" :items="statusItems" class="ms-auto w-32" />
    </div>

    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="error.message"
    />

    <div v-if="loading" class="flex flex-col gap-3">
      <USkeleton class="h-28 w-full" />
      <USkeleton class="h-40 w-full" />
    </div>

    <template v-else-if="summary">
      <UCard>
        <p class="text-sm text-muted">Balance</p>
        <p
          class="mt-1 text-3xl font-semibold tabular-nums"
          :class="isPositive ? 'text-success' : 'text-error'"
        >
          {{ summary.totals.balance }}
        </p>
      </UCard>

      <div class="grid grid-cols-2 gap-4">
        <UCard>
          <p class="text-sm text-muted">Income</p>
          <p class="mt-1 text-xl font-semibold tabular-nums text-success">
            {{ summary.totals.income }}
          </p>
        </UCard>
        <UCard>
          <p class="text-sm text-muted">Expense</p>
          <p class="mt-1 text-xl font-semibold tabular-nums text-error">
            {{ summary.totals.expense }}
          </p>
        </UCard>
      </div>

      <UCard>
        <p class="mb-3 font-medium text-default">Monthly breakdown</p>

        <p v-if="!hasMonthly" class="py-6 text-center text-muted">
          No transactions for this period.
        </p>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-default text-left text-muted">
                <th class="py-2 pe-3 font-medium">Month</th>
                <th class="py-2 pe-3 text-right font-medium">Income</th>
                <th class="py-2 pe-3 text-right font-medium">Expense</th>
                <th class="py-2 text-right font-medium">Balance</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="month in summary.monthly"
                :key="month.month"
                class="border-b border-default/50 last:border-0"
              >
                <td class="py-2 pe-3 text-default">{{ month.month }}</td>
                <td class="py-2 pe-3 text-right tabular-nums text-success">{{ month.income }}</td>
                <td class="py-2 pe-3 text-right tabular-nums text-error">{{ month.expense }}</td>
                <td
                  class="py-2 text-right font-medium tabular-nums"
                  :class="Number(month.balance) >= 0 ? 'text-success' : 'text-error'"
                >
                  {{ month.balance }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </UCard>
    </template>
  </div>
</template>
