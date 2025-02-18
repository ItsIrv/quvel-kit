import { mount } from '@vue/test-utils'
import type { VueWrapper } from '@vue/test-utils'
import { describe, it, expect, beforeEach } from 'vitest'
import WelcomePage from '@/pages/WelcomePage.vue'
import { installQuasarPlugin } from '@quasar/quasar-app-extension-testing-unit-vitest'

installQuasarPlugin()

describe('WelcomePage.vue', () => {
  let wrapper: VueWrapper

  beforeEach(() => {
    wrapper = mount(WelcomePage)
  })

  it('renders the correct title', () => {
    const title = wrapper.find('h1')
    expect(title.exists()).toBe(true)
    expect(title.text()).toContain('Welcome to QuVel Kit')
  })

  it('renders the correct subtitle', () => {
    const subtitle = wrapper.find('p.text-subtitle1')
    expect(subtitle.exists()).toBe(true)
    expect(subtitle.text()).toContain('A full-stack hybrid starter kit for Laravel & Quasar')
  })

  it('renders all main buttons with correct links', () => {
    const buttons = wrapper.findAll('a')

    expect(buttons.length).toBeGreaterThanOrEqual(5)

    const links = [
      { text: 'GitHub Repository', href: 'https://github.com/ItsIrv/quvel-kit/' },
      {
        text: 'Documentation',
        href: 'https://github.com/ItsIrv/quvel-kit/blob/main/docs/README.md',
      },
      { text: 'API Playground', href: 'https://api.quvel.127.0.0.1.nip.io' },
      { text: 'Vitest UI', href: 'https://coverage.quvel.127.0.0.1.nip.io/__vitest__/' },
      { text: 'Laravel Coverage', href: 'https://coverage-api.quvel.127.0.0.1.nip.io' },
      { text: 'Traefik Dashboard', href: 'http://localhost:8080' },
    ]

    links.forEach(({ text, href }) => {
      const btn = buttons.find((b) => b.text().includes(text))

      expect(btn?.exists()).toBe(true)
      expect(btn?.attributes('href')).toBe(href)
    })
  })
})
