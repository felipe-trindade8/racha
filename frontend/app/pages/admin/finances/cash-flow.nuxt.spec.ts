import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { ref } from 'vue'
import CashFlowPage from './cash-flow.vue'
import { CashFlowStatus, type CashFlowType } from '~/types/finance'

const cashFlow: CashFlowType = {
  range: { from: null, to: null },
  status: CashFlowStatus.Paid,
  totals: { income: '150.00', expense: '40.00', balance: '110.00' },
  monthly: [
    { month: '2026-05', income: '50.00', expense: '0.00', balance: '50.00' },
    { month: '2026-06', income: '100.00', expense: '40.00', balance: '60.00' },
  ],
}

const summary = ref<CashFlowType | null>(null)
const loading = ref(false)
const error = ref<{ message: string } | null>(null)
const fetchSummary = vi.fn()
const isAdmin = ref(true)

mockNuxtImport('useFinances', () => () => ({ summary, loading, error, fetchSummary }))
mockNuxtImport('useAuth', () => () => ({ isAdmin }))

describe('cash flow dashboard page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    summary.value = cashFlow
    loading.value = false
    error.value = null
    isAdmin.value = true
    fetchSummary.mockResolvedValue(undefined)
  })

  it('loads the paid summary on mount', async () => {
    await mountSuspended(CashFlowPage)

    expect(fetchSummary).toHaveBeenCalledWith({ status: CashFlowStatus.Paid })
  })

  it('renders the balance and income/expense totals', async () => {
    const wrapper = await mountSuspended(CashFlowPage)

    expect(wrapper.text()).toContain('Balance')
    expect(wrapper.text()).toContain('110.00')
    expect(wrapper.text()).toContain('150.00')
    expect(wrapper.text()).toContain('40.00')
  })

  it('renders a row per month in the breakdown', async () => {
    const wrapper = await mountSuspended(CashFlowPage)

    const rows = wrapper.findAll('tbody tr')
    expect(rows).toHaveLength(2)
    expect(wrapper.text()).toContain('2026-05')
    expect(wrapper.text()).toContain('2026-06')
  })

  it('shows an empty breakdown message when there are no months', async () => {
    summary.value = { ...cashFlow, monthly: [] }
    const wrapper = await mountSuspended(CashFlowPage)

    expect(wrapper.text()).toContain('No transactions for this period.')
  })

  it('surfaces the error message from the composable', async () => {
    error.value = { message: 'Something went wrong.' }
    const wrapper = await mountSuspended(CashFlowPage)

    expect(wrapper.text()).toContain('Something went wrong.')
  })
})
