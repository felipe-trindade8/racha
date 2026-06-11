import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mountSuspended, mockNuxtImport } from '@nuxt/test-utils/runtime'
import { flushPromises } from '@vue/test-utils'
import LoginPage from './login.vue'

const { login, navigateTo } = vi.hoisted(() => ({ login: vi.fn(), navigateTo: vi.fn() }))

mockNuxtImport('useAuth', () => () => ({ login }))
mockNuxtImport('navigateTo', () => navigateTo)

describe('login page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('blocks submit and shows validation errors when fields are empty', async () => {
    const wrapper = await mountSuspended(LoginPage)

    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(login).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Email is required.')
    expect(wrapper.text()).toContain('Password is required.')
  })

  it('submits credentials and navigates home on success', async () => {
    login.mockResolvedValue(undefined)
    const wrapper = await mountSuspended(LoginPage)

    await wrapper.find('input[type="email"]').setValue('admin@example.com')
    await wrapper.find('input[type="password"]').setValue('password')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(login).toHaveBeenCalledWith('admin@example.com', 'password')
    expect(navigateTo).toHaveBeenCalledWith('/')
  })

  it('surfaces the server error message from the envelope', async () => {
    login.mockRejectedValue({ message: 'Invalid credentials.' })
    const wrapper = await mountSuspended(LoginPage)

    await wrapper.find('input[type="email"]').setValue('admin@example.com')
    await wrapper.find('input[type="password"]').setValue('wrong')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(login).toHaveBeenCalled()
    expect(navigateTo).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Invalid credentials.')
  })
})
