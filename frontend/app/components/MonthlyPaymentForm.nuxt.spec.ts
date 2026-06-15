import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import MonthlyPaymentForm from './MonthlyPaymentForm.vue'

describe('MonthlyPaymentForm', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('emits the date and amount on submit', async () => {
    const wrapper = await mountSuspended(MonthlyPaymentForm)

    await wrapper.find('input[type="date"]').setValue('2026-06-15')
    await wrapper.find('input[type="number"]').setValue('50')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    const emitted = wrapper.emitted('submit')
    expect(emitted).toBeTruthy()
    expect(emitted![0]![0]).toEqual({ date: '2026-06-15', amount: 50 })
  })

  it('does not emit when the amount is not greater than zero', async () => {
    const wrapper = await mountSuspended(MonthlyPaymentForm)

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(wrapper.emitted('submit')).toBeFalsy()
    expect(wrapper.text()).toContain('Amount must be greater than zero.')
  })

  it('surfaces the server error message', async () => {
    const wrapper = await mountSuspended(MonthlyPaymentForm, {
      props: { serverError: 'Something went wrong.' },
    })

    expect(wrapper.text()).toContain('Something went wrong.')
  })

  it('maps server validation errors onto the fields', async () => {
    const wrapper = await mountSuspended(MonthlyPaymentForm)

    await wrapper.setProps({ validationErrors: { amount: ['The amount must be a number.'] } })
    await flushPromises()

    expect(wrapper.text()).toContain('The amount must be a number.')
  })
})
