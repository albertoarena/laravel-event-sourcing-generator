import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

// Project site on GitHub Pages: https://albertoarena.github.io/laravel-event-sourcing-generator
export default defineConfig({
  site: 'https://albertoarena.github.io',
  base: '/laravel-event-sourcing-generator',
  integrations: [
    starlight({
      title: 'Laravel Event Sourcing Generator',
      description:
        'Scaffold complete event-sourced domains for spatie/laravel-event-sourcing with a single Artisan command.',
      head: [
        { tag: 'meta', attrs: { property: 'og:image', content: 'https://albertoarena.github.io/laravel-event-sourcing-generator/cover.png' } },
        { tag: 'meta', attrs: { name: 'twitter:image', content: 'https://albertoarena.github.io/laravel-event-sourcing-generator/cover.png' } },
        { tag: 'meta', attrs: { name: 'twitter:card', content: 'summary_large_image' } },
      ],
      social: [
        { icon: 'github', label: 'GitHub', href: 'https://github.com/albertoarena/laravel-event-sourcing-generator' },
      ],
      editLink: {
        baseUrl: 'https://github.com/albertoarena/laravel-event-sourcing-generator/edit/main/website/',
      },
      customCss: ['./src/styles/custom.css'],
      sidebar: [
        {
          label: 'Introduction',
          items: [{ label: 'Overview', link: '/' }],
        },
        {
          label: 'Getting Started',
          items: [
            { label: 'Installation', link: '/getting-started/installation/' },
            { label: 'Quick start', link: '/getting-started/quick-start/' },
          ],
        },
        {
          label: 'Guide',
          items: [
            { label: 'Basic usage', link: '/guide/basic-usage/' },
            { label: 'Domain and namespace', link: '/guide/domain-and-namespace/' },
            { label: 'Advanced usage', link: '/guide/advanced-usage/' },
            { label: 'Unit tests', link: '/guide/unit-tests/' },
            { label: 'Migrations', link: '/guide/migrations/' },
          ],
        },
        {
          label: 'Reference',
          items: [
            { label: 'Command options', link: '/reference/command-options/' },
            { label: 'Compatibility', link: '/reference/compatibility/' },
            { label: 'Unsupported columns', link: '/reference/unsupported-columns/' },
          ],
        },
        {
          label: 'Project',
          items: [
            {
              label: 'Plans',
              link: 'https://github.com/albertoarena/laravel-event-sourcing-generator/tree/main/docs/plans',
              attrs: { target: '_blank', rel: 'noopener noreferrer' },
              badge: 'GitHub',
            },
            {
              label: 'Changelog',
              link: 'https://github.com/albertoarena/laravel-event-sourcing-generator/blob/main/CHANGELOG.md',
              attrs: { target: '_blank', rel: 'noopener noreferrer' },
              badge: 'GitHub',
            },
            {
              label: 'Contributing',
              link: 'https://github.com/albertoarena/laravel-event-sourcing-generator/blob/main/CONTRIBUTING.md',
              attrs: { target: '_blank', rel: 'noopener noreferrer' },
              badge: 'GitHub',
            },
          ],
        },
      ],
    }),
  ],
});
