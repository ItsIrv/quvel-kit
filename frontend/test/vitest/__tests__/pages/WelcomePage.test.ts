import { mount, type VueWrapper } from '@vue/test-utils';
import { describe, it, expect, beforeEach } from 'vitest';
import WelcomePage from '@/pages/WelcomePage.vue';
import { installQuasarPlugin } from '@quasar/quasar-app-extension-testing-unit-vitest';
import { createI18n } from 'vue-i18n';
import welcome from 'src/i18n/en-US/welcome';

// Install Quasar testing plugin
installQuasarPlugin();

// Initialize i18n with actual translations
const i18n = createI18n({
  locale: 'en-US',
  messages: {
    'en-US': {
      welcome,
    },
  },
});

describe('WelcomePage.vue', () => {
  let wrapper: VueWrapper;

  beforeEach(() => {
    wrapper = mount(WelcomePage, {
      global: {
        plugins: [i18n],
      },
    });
  });

  it('renders the correct title', () => {
    const title = wrapper.find('h1');
    expect(title.exists()).toBe(true);
    expect(title.text()).toContain(welcome.title.replace('{appName}', 'QuVel Kit'));
  });

  it('renders the correct subtitle', () => {
    const subtitle = wrapper.find('p.text-subtitle1');
    expect(subtitle.exists()).toBe(true);
    expect(subtitle.text()).toContain(welcome.description);
  });

  it('renders all main buttons with correct links', () => {
    const buttons = wrapper.findAll('a');

    expect(buttons.length).toBeGreaterThanOrEqual(5);

    const links = [
      { text: welcome.links.github, href: 'https://github.com/ItsIrv/quvel-kit/' },
      {
        text: welcome.links.docs,
        href: 'https://github.com/ItsIrv/quvel-kit/blob/main/docs/README.md',
      },
      { text: welcome.links.api, href: 'https://api.quvel.127.0.0.1.nip.io' },
      { text: welcome.links.vitest, href: 'https://coverage.quvel.127.0.0.1.nip.io/__vitest__/' },
      { text: welcome.links.coverage, href: 'https://coverage-api.quvel.127.0.0.1.nip.io' },
      { text: welcome.links.traefik, href: 'http://localhost:8080' },
    ];

    links.forEach(({ text, href }) => {
      const btn = buttons.find((b) => b.text().includes(text));

      expect(btn?.exists()).toBe(true);
      expect(btn?.attributes('href')).toBe(href);
    });
  });
});
