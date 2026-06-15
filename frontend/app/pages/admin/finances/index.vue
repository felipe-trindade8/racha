<script setup lang="ts">
/**
 * Financial transactions list (administrator-only).
 *
 * Mobile-first list of transactions with type/status/month filters and
 * server-side pagination, driven by {@link useFinances}. Create and edit happen
 * in a modal sharing {@link TransactionForm}; open transactions can be marked
 * paid and paid ones reopened (the backend locks paid transactions from edits).
 * Listing is administrator-only on the backend (FinancialTransactionPolicy), so
 * the page is gated by the `admin` middleware.
 */
import type { ApiError, ValidationErrors } from '~/types/api'
import {
  TransactionStatus,
  TransactionType,
  type FinancialTransaction,
  type FinancialTransactionPayload,
  type FinancialTransactionQuery,
} from '~/types/finance'

definePageMeta({ middleware: 'admin' })

const PER_PAGE = 15
const ALL = 'all'

const { transactions, meta, loading, error, fetchList, create, update, pay, reopen } = useFinances()
const { players: roster, fetchList: loadRoster } = usePlayers()

const typeFilter = ref<string>(ALL)
const statusFilter = ref<string>(ALL)
const monthFilter = ref<string>('')
const page = ref(1)

const typeItems = [
  { label: 'All types', value: ALL },
  { label: 'Income', value: TransactionType.Income },
  { label: 'Expense', value: TransactionType.Expense },
]

const statusItems = [
  { label: 'All statuses', value: ALL },
  { label: 'Open', value: TransactionStatus.Open },
  { label: 'Paid', value: TransactionStatus.Paid },
]

const statusBadgeColor: Record<TransactionStatus, 'warning' | 'success'> = {
  [TransactionStatus.Open]: 'warning',
  [TransactionStatus.Paid]: 'success',
}

const query = computed<FinancialTransactionQuery>(() => ({
  type: typeFilter.value === ALL ? undefined : (typeFilter.value as TransactionType),
  status: statusFilter.value === ALL ? undefined : (statusFilter.value as TransactionStatus),
  month: monthFilter.value || undefined,
  per_page: PER_PAGE,
  page: page.value,
}))

const hasResults = computed(() => transactions.value.length > 0)

const playerName = computed<Record<number, string>>(() =>
  Object.fromEntries(roster.value.map((player) => [player.id, player.nickname || player.name])),
)

/** Refetch the current page; errors are surfaced through the composable's `error`. */
async function load() {
  try {
    await fetchList(query.value)
  } catch {
    // The error is captured in `error` by useFinances and rendered below.
  }
}

// Load the roster (for the player select) and the first page, then refetch
// whenever the query changes.
await Promise.all([loadRoster({ per_page: 100 }).catch(() => undefined), load()])
watch(query, load)

// Reset to the first page whenever a filter changes.
watch([typeFilter, statusFilter, monthFilter], () => {
  page.value = 1
})

// --- Create / edit modal ---------------------------------------------------
const modalOpen = ref(false)
const editing = ref<FinancialTransaction | null>(null)
const serverError = ref<string | null>(null)
const validationErrors = ref<ValidationErrors | null>(null)

function openCreate() {
  editing.value = null
  serverError.value = null
  validationErrors.value = null
  modalOpen.value = true
}

function openEdit(transaction: FinancialTransaction) {
  editing.value = transaction
  serverError.value = null
  validationErrors.value = null
  modalOpen.value = true
}

async function onSubmit(payload: FinancialTransactionPayload) {
  serverError.value = null
  validationErrors.value = null

  try {
    if (editing.value) {
      await update(editing.value.id, payload)
    } else {
      await create(payload)
    }
    modalOpen.value = false
  } catch (e) {
    const apiError = e as ApiError
    serverError.value = apiError.message
    validationErrors.value = apiError.errors ?? null
  }
}

/** Mark an open transaction paid; the row updates via the composable. */
async function onPay(transaction: FinancialTransaction) {
  try {
    await pay(transaction.id)
  } catch {
    // Surfaced through `error`.
  }
}

/** Reopen a paid transaction so it can be edited again. */
async function onReopen(transaction: FinancialTransaction) {
  try {
    await reopen(transaction.id)
  } catch {
    // Surfaced through `error`.
  }
}
</script>

<template>
  <div class="mx-auto flex max-w-2xl flex-col gap-4">
    <div class="flex items-center justify-between gap-3">
      <h1 class="text-2xl font-semibold text-default">Finances</h1>
      <div class="flex items-center gap-2">
        <UButton
          to="/admin/finances/cash-flow"
          icon="i-lucide-chart-column"
          label="Cash flow"
          color="neutral"
          variant="subtle"
        />
        <UButton icon="i-lucide-plus" label="New transaction" color="primary" @click="openCreate" />
      </div>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
      <USelect v-model="typeFilter" :items="typeItems" class="w-full sm:w-40" />
      <USelect v-model="statusFilter" :items="statusItems" class="w-full sm:w-40" />
      <UInput v-model="monthFilter" type="month" class="w-full sm:w-44" aria-label="Month" />
    </div>

    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      :title="error.message"
    />

    <div v-if="loading" class="flex flex-col gap-3">
      <USkeleton v-for="n in 4" :key="n" class="h-24 w-full" />
    </div>

    <p v-else-if="!hasResults" class="py-8 text-center text-muted">No transactions found.</p>

    <ul v-else class="flex flex-col gap-3">
      <li v-for="transaction in transactions" :key="transaction.id">
        <UCard>
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="truncate font-medium text-default">{{ transaction.description }}</p>
              <p class="text-sm text-muted">
                {{ transaction.date }}
                <template v-if="transaction.playerId">
                  · {{ playerName[transaction.playerId] ?? 'Player' }}
                </template>
              </p>
            </div>
            <p
              class="shrink-0 font-semibold tabular-nums"
              :class="transaction.type === TransactionType.Income ? 'text-success' : 'text-error'"
            >
              {{ transaction.type === TransactionType.Income ? '+' : '−' }}{{ transaction.amount }}
            </p>
          </div>

          <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <UBadge :color="statusBadgeColor[transaction.status]" variant="subtle">
              {{ transaction.status }}
            </UBadge>

            <div class="flex items-center gap-2">
              <template v-if="transaction.status === TransactionStatus.Open">
                <UButton
                  icon="i-lucide-pencil"
                  color="neutral"
                  variant="ghost"
                  size="sm"
                  :aria-label="`Edit ${transaction.description}`"
                  @click="openEdit(transaction)"
                />
                <UButton
                  icon="i-lucide-check"
                  color="primary"
                  variant="subtle"
                  size="sm"
                  label="Mark paid"
                  @click="onPay(transaction)"
                />
              </template>
              <UButton
                v-else
                icon="i-lucide-rotate-ccw"
                color="neutral"
                variant="subtle"
                size="sm"
                label="Reopen"
                @click="onReopen(transaction)"
              />
            </div>
          </div>
        </UCard>
      </li>
    </ul>

    <div v-if="meta && meta.last_page > 1" class="flex justify-center">
      <UPagination v-model:page="page" :total="meta.total" :items-per-page="PER_PAGE" />
    </div>

    <UModal v-model:open="modalOpen" :title="editing ? 'Edit transaction' : 'New transaction'">
      <template #body>
        <TransactionForm
          :key="editing?.id ?? 'new'"
          :initial-values="editing"
          :players="roster"
          :submit-label="editing ? 'Save changes' : 'Create transaction'"
          :submitting="loading"
          :server-error="serverError"
          :validation-errors="validationErrors"
          @submit="onSubmit"
        />
      </template>
    </UModal>
  </div>
</template>
