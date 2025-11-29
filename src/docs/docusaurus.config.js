const lightCodeTheme = require('prism-react-renderer').themes.oneLight;
const darkCodeTheme = require('prism-react-renderer').themes.nightOwl;

// With JSDoc @type annotations, IDEs can provide config autocompletion
/** @type {import('@docusaurus/types').DocusaurusConfig} */
(module.exports = {
  title: 'Yap Docs',
  tagline: 'Yap is cool',
  url: 'https://yapdocs.com',
  baseUrl: '/',
  onBrokenLinks: 'throw',
  markdown: {
    hooks: {
        onBrokenMarkdownLinks: 'warn',
    }
  },
  favicon: 'img/favicon.ico',
  organizationName: 'bmlt-enabled', // Usually your GitHub org/username.
  projectName: 'yap', // Usually your repo name.
  plugins: [],
  presets: [
    [
      '@docusaurus/preset-classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          sidebarPath: require.resolve('./sidebars.js'),
          // Please change this to your repo.
          editUrl: 'https://github.com/bmlt-enabled/yap/edit/main/docs/',
          routeBasePath: '/',
        },
        blog: {
          showReadingTime: true,
          // Please change this to your repo.
          editUrl:
            'https://github.com/bmlt-enabled/yap/edit/main/docs/',
        },
        theme: {
          customCss: require.resolve('./src/css/custom.css'),
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      colorMode: {
        defaultMode: "dark"
      },
      algolia: {
        // The application ID provided by Algolia
        appId: 'UXFW8XBXNC',
        // Public API key: it is safe to commit it
        apiKey: '964f47c30775ccc32cbb73a08c4ff778',
        indexName: 'yapdocs',
      },
      navbar: {
        title: 'Yap Docs',
        logo: {
          alt: 'Yap Logo',
          src: 'img/yap.png',
        },
        items: [
          // {
          //   type: 'doc',
          //   docId: 'intro',
          //   position: 'left',
          //   label: 'Tutorial',
          // },
          {to: '/blog', label: 'Blog', position: 'left'},
          {
            href: 'https://github.com/bmlt-enabled/yap/',
            label: 'GitHub',
            position: 'right',
          },
        ],
      },
      footer: {
        style: 'dark',
        links: [
          {
            title: 'Docs',
            items: [
              {
                label: 'Home',
                to: '/',
              },
            ],
          },
          {
            title: 'Community',
            items: [
              {
                label: 'BMLT Site',
                href: 'https://bmlt.app',
              },
              {
                label: 'Facebook',
                href: 'https://www.facebook.com/groups/bmltapp/',
              },
              {
                label: 'Twitter',
                href: 'https://twitter.com/BMLT_NA',
              },
            ],
          },
          {
            title: 'More',
            items: [
              {
                label: 'Blog',
                to: '/blog',
              },
              {
                label: 'GitHub',
                href: 'https://github.com/bmlt-enabled/yap',
              },
            ],
          },
        ],
        copyright: `${new Date().getFullYear()} Yap Docs. Built with Docusaurus.`,
      },
      prism: {
        theme: lightCodeTheme,
        darkTheme: darkCodeTheme,
      },
    }),
});
