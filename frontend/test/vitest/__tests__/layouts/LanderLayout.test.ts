import { mount } from '@vue/test-utils'
import { describe, it, expect } from 'vitest'
import LanderLayout from '../../../../src/layouts/LanderLayout.vue'
import { installQuasarPlugin } from '@quasar/quasar-app-extension-testing-unit-vitest'

// Install Quasar Plugin before running tests
installQuasarPlugin()

describe('LanderLayout.vue', () => {
  it('should mount properly', () => {
    const wrapper = mount(LanderLayout, {})

    expect(wrapper.exists()).toBe(true)
  })

  it('should contain a router-view', () => {
    const wrapper = mount(LanderLayout, {
      global: {
        stubs: {
          'router-view': true,
        },
      },
    })

    expect(wrapper.findComponent({ name: 'RouterView' }).exists()).toBe(true)
  })
})
