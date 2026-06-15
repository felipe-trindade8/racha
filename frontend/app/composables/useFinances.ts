/**
 * Finance list/summary state and mutations.
 *
 * Exposes the transaction list plus pagination metadata, the cash-flow summary,
 * loading/error flags and the create/update/pay/reopen/generate mutations. List
 * and summary are held in shared SSR-friendly state via `useState`; business
 * logic lives here, API communication in {@link FinanceService} (see
 * docs/coding-standards.md).
 */
import { FinanceService } from '~/services/FinanceService'
import type { ApiError, PaginationMeta } from '~/types/api'
import {
  TransactionStatus,
  type CashFlowQuery,
  type CashFlowType,
  type FinancialTransaction,
  type FinancialTransactionPayload,
  type FinancialTransactionQuery,
  type MonthlyPaymentPayload,
} from '~/types/finance'

export function useFinances() {
  const transactions = useState<FinancialTransaction[]>('finances.list', () => [])
  const meta = useState<PaginationMeta | null>('finances.meta', () => null)
  const summary = useState<CashFlowType | null>('finances.summary', () => null)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)
  const service = new FinanceService()

  /**
   * Run a service call while tracking loading and error state. The error is
   * captured for consumers and re-thrown so callers can react to failures.
   */
  async function run<T>(action: () => Promise<T>): Promise<T> {
    loading.value = true
    error.value = null
    try {
      return await action()
    } catch (e) {
      error.value = e as ApiError
      throw e
    } finally {
      loading.value = false
    }
  }

  /** Load a page of transactions into the shared list. */
  async function fetchList(query?: FinancialTransactionQuery) {
    await run(async () => {
      const { data, meta: pageMeta } = await service.list(query)
      transactions.value = data
      meta.value = pageMeta
    })
  }

  /** Load the cash-flow summary into shared state. */
  async function fetchSummary(query?: CashFlowQuery) {
    return run(async () => {
      const { data } = await service.cashFlow(query)
      summary.value = data
      return data
    })
  }

  /** Create a transaction and prepend it to the current list. */
  function create(payload: FinancialTransactionPayload) {
    return run(async () => {
      const { data } = await service.create(payload)
      transactions.value = [data, ...transactions.value]
      return data
    })
  }

  /** Update a transaction and replace it in the current list when present. */
  function update(id: number, payload: Partial<FinancialTransactionPayload>) {
    return run(async () => {
      const { data } = await service.update(id, payload)
      replaceInList(data)
      return data
    })
  }

  /** Mark a transaction as paid and replace it in the current list. */
  function pay(id: number) {
    return changeStatus(id, TransactionStatus.Paid)
  }

  /** Reopen a paid transaction and replace it in the current list. */
  function reopen(id: number) {
    return changeStatus(id, TransactionStatus.Open)
  }

  /** Generate the monthly payment charges for active players. */
  function generateMonthly(payload: MonthlyPaymentPayload) {
    return run(async () => (await service.generateMonthly(payload)).data)
  }

  function changeStatus(id: number, status: TransactionStatus) {
    return run(async () => {
      const { data } = await service.updateStatus(id, status)
      replaceInList(data)
      return data
    })
  }

  function replaceInList(transaction: FinancialTransaction) {
    transactions.value = transactions.value.map((current) =>
      current.id === transaction.id ? transaction : current,
    )
  }

  return {
    transactions,
    meta,
    summary,
    loading,
    error,
    fetchList,
    fetchSummary,
    create,
    update,
    pay,
    reopen,
    generateMonthly,
  }
}
