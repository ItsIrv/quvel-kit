import { mount } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import LanderLayout from '../../../../src/layouts/LanderLayout.vue'
import { installQuasarPlugin } from '@quasar/quasar-app-extension-testing-unit-vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import { defineComponent } from 'vue'

// Install Quasar Plugin before running tests
installQuasarPlugin()

// Create a dummy component for testing the router-view
const DummyComponent = defineComponent({ template: '<div data-testid="dummy">Test Route</div>' })

// Setup router
const router = createRouter({
  history: createMemoryHistory(),
  routes: [{ path: '/', component: DummyComponent }],
})

describe('LanderLayout.vue', () => {
  beforeEach(async () => {
    void router.push('/')
    await router.isReady()
  })

  it('should mount properly', () => {
    const wrapper = mount(LanderLayout, {
      global: {
        plugins: [router],
      },
    })

    expect(wrapper.exists()).toBe(true)
  })

  it('should contain a router-view', () => {
    const wrapper = mount(LanderLayout, {
      global: {
        plugins: [router],
      },
    })

    expect(wrapper.findComponent({ name: 'RouterView' }).exists()).toBe(true)
  })

  it('should render the dummy component inside router-view', () => {
    const wrapper = mount(LanderLayout, {
      global: {
        plugins: [router],
      },
    })

    expect(wrapper.find('[data-testid="dummy"]').exists()).toBe(true)
    expect(wrapper.html()).toContain('Test Route')
  })
})
