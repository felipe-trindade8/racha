import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useFinances } from './useFinances'
import type { ApiError, PaginationMeta } from '~/types/api'
import {
  TransactionStatus,
  TransactionType,
  type CashFlowType,
  type FinancialTransaction,
} from '~/types/finance'

const transaction: FinancialTransaction = {
  id: 1,
  playerId: 7,
  description: 'Monthly fee',
  amount: '50.00',
  type: TransactionType.Income,
  date: '2026-06-14',
  status: TransactionStatus.Open,
  createdAt: '2026-06-14T00:00:00Z',
  updatedAt: '2026-06-14T00:00:00Z',
}

const meta: PaginationMeta = { current_page: 1, per_page: 15, total: 1, last_page: 1 }

const cashFlow: CashFlowType = {
  range: { from: '2026-06', to: '2026-06' },
  status: 'paid',
  totals: { income: '100.00', expense: '40.00', balance: '60.00' },
  monthly: [{ month: '2026-06', income: '100.00', expense: '40.00', balance: '60.00' }],
}

const list = vi.fn()
const create = vi.fn()
const update = vi.fn()
const updateStatus = vi.fn()
const generateMonthly = vi.fn()
const cashFlowFn = vi.fn()

vi.mock('~/services/FinanceService', () => ({
  FinanceService: class {
    list = list
    create = create
    update = update
    updateStatus = updateStatus
    generateMonthly = generateMonthly
    cashFlow = cashFlowFn
  },
}))

describe('useFinances', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset shared state between tests.
    useState<FinancialTransaction[]>('finances.list').value = []
    useState<PaginationMeta | null>('finances.meta').value = null
    useState<CashFlowType | null>('finances.summary').value = null
  })

  it('starts with empty list, summary, and no loading or error', () => {
    const { transactions, meta: pageMeta, summary, loading, error } = useFinances()

    expect(transactions.value).toEqual([])
    expect(pageMeta.value).toBeNull()
    expect(summary.value).toBeNull()
    expect(loading.value).toBe(false)
    expect(error.value).toBeNull()
  })

  it('loads transactions and pagination meta on fetchList', async () => {
    list.mockResolvedValue({ data: [transaction], meta })

    const { transactions, meta: pageMeta, fetchList } = useFinances()
    await fetchList({ status: TransactionStatus.Open, month: '2026-06' })

    expect(list).toHaveBeenCalledWith({ status: TransactionStatus.Open, month: '2026-06' })
    expect(transactions.value).toEqual([transaction])
    expect(pageMeta.value).toEqual(meta)
  })

  it('loads the cash-flow summary on fetchSummary', async () => {
    cashFlowFn.mockResolvedValue({ data: cashFlow })

    const { summary, fetchSummary } = useFinances()
    const result = await fetchSummary({ from: '2026-06', to: '2026-06' })

    expect(cashFlowFn).toHaveBeenCalledWith({ from: '2026-06', to: '2026-06' })
    expect(result).toEqual(cashFlow)
    expect(summary.value).toEqual(cashFlow)
  })

  it('captures the error and re-throws when a fetch fails', async () => {
    const failure: ApiError = { message: 'Forbidden' }
    list.mockRejectedValue(failure)

    const { error, loading, fetchList } = useFinances()

    await expect(fetchList()).rejects.toBe(failure)
    expect(error.value).toEqual(failure)
    expect(loading.value).toBe(false)
  })

  it('prepends a created transaction to the list', async () => {
    const existing = { ...transaction, id: 2 }
    useState<FinancialTransaction[]>('finances.list').value = [existing]
    create.mockResolvedValue({ data: transaction })

    const { transactions, create: doCreate } = useFinances()
    const result = await doCreate({
      description: 'Monthly fee',
      amount: 50,
      type: TransactionType.Income,
      date: '2026-06-14',
    })

    expect(result).toEqual(transaction)
    expect(transactions.value).toEqual([transaction, existing])
  })

  it('replaces an updated transaction in the list', async () => {
    useState<FinancialTransaction[]>('finances.list').value = [transaction]
    const updated = { ...transaction, description: 'Adjusted fee' }
    update.mockResolvedValue({ data: updated })

    const { transactions, update: doUpdate } = useFinances()
    await doUpdate(transaction.id, { description: 'Adjusted fee' })

    expect(update).toHaveBeenCalledWith(transaction.id, { description: 'Adjusted fee' })
    expect(transactions.value).toEqual([updated])
  })

  it('marks a transaction as paid and replaces it in the list', async () => {
    useState<FinancialTransaction[]>('finances.list').value = [transaction]
    const paid = { ...transaction, status: TransactionStatus.Paid }
    updateStatus.mockResolvedValue({ data: paid })

    const { transactions, pay } = useFinances()
    await pay(transaction.id)

    expect(updateStatus).toHaveBeenCalledWith(transaction.id, TransactionStatus.Paid)
    expect(transactions.value).toEqual([paid])
  })

  it('reopens a paid transaction and replaces it in the list', async () => {
    const paid = { ...transaction, status: TransactionStatus.Paid }
    useState<FinancialTransaction[]>('finances.list').value = [paid]
    updateStatus.mockResolvedValue({ data: transaction })

    const { transactions, reopen } = useFinances()
    await reopen(transaction.id)

    expect(updateStatus).toHaveBeenCalledWith(transaction.id, TransactionStatus.Open)
    expect(transactions.value).toEqual([transaction])
  })

  it('returns the generated transactions on generateMonthly', async () => {
    generateMonthly.mockResolvedValue({ data: [transaction] })

    const { generateMonthly: doGenerate } = useFinances()
    const result = await doGenerate({ date: '2026-06-14', amount: 50 })

    expect(generateMonthly).toHaveBeenCalledWith({ date: '2026-06-14', amount: 50 })
    expect(result).toEqual([transaction])
  })
})
