import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import TransactionForm from './TransactionForm.vue'
import { PlayerStatus, type Player } from '~/types/player'
import { TransactionStatus, TransactionType, type FinancialTransaction } from '~/types/finance'

const roster: Player[] = [
  {
    id: 7,
    name: 'Marcos Cafu',
    nickname: 'Cafu',
    rating: 5,
    status: PlayerStatus.Active,
    phone: null,
    positions: [],
    createdAt: '2026-06-12T00:00:00Z',
    updatedAt: '2026-06-12T00:00:00Z',
  },
]

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

describe('TransactionForm', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('emits a payload built from the initial values on submit', async () => {
    const wrapper = await mountSuspended(TransactionForm, {
      props: { initialValues: transaction, players: roster },
    })

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted![0]![0]).toEqual({
      description: 'Monthly fee',
      amount: 50,
      type: TransactionType.Income,
      date: '2026-06-14',
      player_id: 7,
    })
  })

  it('sends a null player_id when no player is selected', async () => {
    const wrapper = await mountSuspended(TransactionForm, {
      props: {
        initialValues: { ...transaction, playerId: null },
        players: roster,
      },
    })

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const payload = wrapper.emitted('submit')![0]![0] as { player_id: number | null }
    expect(payload.player_id).toBeNull()
  })

  it('does not emit when the description is empty (client validation)', async () => {
    const wrapper = await mountSuspended(TransactionForm, {
      props: { initialValues: { ...transaction, description: '' }, players: roster },
    })

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('Description is required.')
  })

  it('does not emit when the amount is not greater than zero', async () => {
    const wrapper = await mountSuspended(TransactionForm, {
      props: { initialValues: { ...transaction, amount: '0' }, players: roster },
    })

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('Amount must be greater than zero.')
  })

  it('surfaces the server error message', async () => {
    const wrapper = await mountSuspended(TransactionForm, {
      props: { players: roster, serverError: 'Something went wrong.' },
    })

    expect(wrapper.text()).toContain('Something went wrong.')
  })

  it('maps server validation errors onto the fields', async () => {
    const wrapper = await mountSuspended(TransactionForm, { props: { players: roster } })

    await wrapper.setProps({ validationErrors: { amount: ['The amount must be a number.'] } })
    await flushPromises()

    expect(wrapper.text()).toContain('The amount must be a number.')
  })
})
