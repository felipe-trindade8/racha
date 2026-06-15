import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import { ref } from 'vue'
import FinancesPage from './index.vue'
import MonthlyPaymentForm from '~/components/MonthlyPaymentForm.vue'
import type { PaginationMeta } from '~/types/api'
import { TransactionStatus, TransactionType, type FinancialTransaction } from '~/types/finance'

const monthlyFee: FinancialTransaction = {
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

const transactions = ref<FinancialTransaction[]>([])
const meta = ref<PaginationMeta | null>(null)
const loading = ref(false)
const error = ref<{ message: string } | null>(null)
const fetchList = vi.fn()
const create = vi.fn()
const update = vi.fn()
const pay = vi.fn()
const reopen = vi.fn()
const generateMonthly = vi.fn()

const roster = ref([{ id: 7, name: 'Marcos Cafu', nickname: 'Cafu' }])
const loadRoster = vi.fn()
const isAdmin = ref(true)

mockNuxtImport('useFinances', () => () => ({
  transactions,
  meta,
  loading,
  error,
  fetchList,
  create,
  update,
  pay,
  reopen,
  generateMonthly,
}))
mockNuxtImport('usePlayers', () => () => ({ players: roster, fetchList: loadRoster }))
mockNuxtImport('useAuth', () => () => ({ isAdmin }))

describe('finances list page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    transactions.value = [monthlyFee]
    meta.value = { current_page: 1, per_page: 15, total: 1, last_page: 1 }
    loading.value = false
    error.value = null
    isAdmin.value = true
    fetchList.mockResolvedValue(undefined)
    loadRoster.mockResolvedValue(undefined)
  })

  it('loads the roster and the first page with default query on mount', async () => {
    await mountSuspended(FinancesPage)

    expect(loadRoster).toHaveBeenCalledWith({ per_page: 100 })
    expect(fetchList).toHaveBeenCalledWith({
      type: undefined,
      status: undefined,
      month: undefined,
      per_page: 15,
      page: 1,
    })
  })

  it('renders a transaction with its amount, date and status', async () => {
    const wrapper = await mountSuspended(FinancesPage)

    expect(wrapper.text()).toContain('Monthly fee')
    expect(wrapper.text()).toContain('+50.00')
    expect(wrapper.text()).toContain('2026-06-14')
    expect(wrapper.text()).toContain('open')
  })

  it('shows an empty state when there are no transactions', async () => {
    transactions.value = []
    const wrapper = await mountSuspended(FinancesPage)

    expect(wrapper.text()).toContain('No transactions found.')
  })

  it('refetches with the month filter and resets to the first page when it changes', async () => {
    const wrapper = await mountSuspended(FinancesPage)
    fetchList.mockClear()

    await wrapper.find('input[type="month"]').setValue('2026-06')
    await flushPromises()

    expect(fetchList).toHaveBeenCalledWith(expect.objectContaining({ month: '2026-06', page: 1 }))
  })

  it('marks a transaction paid through the composable', async () => {
    pay.mockResolvedValue(undefined)
    const wrapper = await mountSuspended(FinancesPage)

    const payButton = wrapper.findAll('button').find((b) => b.text().includes('Mark paid'))
    expect(payButton).toBeDefined()
    await payButton!.trigger('click')
    await flushPromises()

    expect(pay).toHaveBeenCalledWith(monthlyFee.id)
  })

  it('offers reopen instead of mark paid for a paid transaction', async () => {
    transactions.value = [{ ...monthlyFee, status: TransactionStatus.Paid }]
    const wrapper = await mountSuspended(FinancesPage)

    expect(wrapper.text()).toContain('Reopen')
    expect(wrapper.text()).not.toContain('Mark paid')
  })

  it('surfaces the error message from the composable', async () => {
    error.value = { message: 'Something went wrong.' }
    const wrapper = await mountSuspended(FinancesPage)

    expect(wrapper.text()).toContain('Something went wrong.')
  })

  it('offers a generate monthly payments action to an administrator', async () => {
    const wrapper = await mountSuspended(FinancesPage)

    const button = wrapper.findAll('button').find((b) => b.text().includes('Generate monthly'))
    expect(button).toBeDefined()
  })

  it('generates monthly payments and refreshes the list on submit', async () => {
    generateMonthly.mockResolvedValue([monthlyFee, { ...monthlyFee, id: 2 }])
    const wrapper = await mountSuspended(FinancesPage)

    // Open the modal so the form mounts, then drive it via its submit emit.
    const button = wrapper.findAll('button').find((b) => b.text().includes('Generate monthly'))
    await button!.trigger('click')
    await flushPromises()

    fetchList.mockClear()
    const form = wrapper.findComponent(MonthlyPaymentForm)
    expect(form.exists()).toBe(true)
    form.vm.$emit('submit', { date: '2026-06-15', amount: 50 })
    await flushPromises()

    expect(generateMonthly).toHaveBeenCalledWith({ date: '2026-06-15', amount: 50 })
    // The list is refetched so the generated charges appear.
    expect(fetchList).toHaveBeenCalledTimes(1)
    expect(wrapper.text()).toContain('2 monthly payment(s) for 2026-06.')
  })
})
