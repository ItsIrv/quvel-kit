import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import IndexPage from '../../../../src/pages/IndexPage.vue'
import { installQuasarPlugin } from '@quasar/quasar-app-extension-testing-unit-vitest'

installQuasarPlugin()

describe('IndexPage.vue', () => {
  it('renders the correct title', async () => {
    const wrapper = mount(IndexPage)

    // Ensure the element exists before calling text()
    const title = wrapper.find('h1')
    expect(title.exists()).toBe(true)
    expect(title.text()).toContain('Elevate Your Development with QuVel Kit')
  })

  it('renders the correct subtitle', () => {
    const wrapper = mount(IndexPage)

    const subtitle = wrapper.find('p.text-subtitle1')
    expect(subtitle.exists()).toBe(true)
    expect(subtitle.text()).toContain('A powerful, hybrid starter kit for Laravel & Quasar')
  })

  it('has a working "Get Started" button', () => {
    const wrapper = mount(IndexPage)

    const button = wrapper.find('a')
    expect(button.exists()).toBe(true)
    expect(button.attributes('href')).toBe('https://github.com/ItsIrv/quvel-kit/')
  })
})
