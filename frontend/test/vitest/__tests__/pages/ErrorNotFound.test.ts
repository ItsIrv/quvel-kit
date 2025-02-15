import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import Error404Page from '../../../../src/pages/ErrorNotFound.vue'
import { installQuasarPlugin } from '@quasar/quasar-app-extension-testing-unit-vitest'

// Install Quasar Plugin before running tests
installQuasarPlugin()

describe('ErrorNotFound.vue', () => {
  it('should mount properly', () => {
    const wrapper = mount(Error404Page)
    expect(wrapper.exists()).toBe(true)
  })

  it('should display 404 error message', () => {
    const wrapper = mount(Error404Page)
    expect(wrapper.text()).toContain('404')
    expect(wrapper.text()).toContain('Oops. Nothing here...')
  })

  it('should have a button to go home', () => {
    const wrapper = mount(Error404Page)
    const button = wrapper.find('button')
    expect(button.exists()).toBe(true)
    expect(button.text()).toBe('Go Home')
  })
})
