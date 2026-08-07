# ADR 005: Nuxt UI Design Foundation

**Status:** Accepted and implemented by TF-26

ToggleFlow adopts Nuxt UI within the existing Vue 3 and Vite dashboard. This does not
authorize Nuxt application migration, server-side rendering, or Nuxt Content.
ToggleFlow owns semantic tokens, accessible workflows, and product components. The
landing template is a composition reference; its lime branding is replaced by the
approved teal/mint-and-zinc ToggleFlow themes.

The initial TF-26 product decision selected indigo for Light primary. Jira comment
10048 explicitly superseded that historical choice with one teal/mint brand family
across Light and Dark.

## Implementation

The dashboard pins `@nuxt/ui` 4.10.0 and integrates it through `@nuxt/ui/vite` and
`@nuxt/ui/vue-plugin`. Vue Router, the SPA entry point, Vite build, and static
deployment remain application-owned. Nuxt, Nuxt Content, SSR, file-based routing,
remote fonts, and a second component framework are not installed.

`src/app/app.config.ts` maps Nuxt UI roles to the approved semantic palette.
`src/app/app.css` is the single Tailwind/Nuxt UI import and owns ToggleFlow-only
tokens. The root `UApp` supplies overlays, tooltips, and toasts. A development-only
`/__ui-foundation` route validates primitives without adding a production route.

TF-26 also delivers Light, Dark, and System presentation preferences. The app owns a
closed preference controller, accessible three-choice selector, browser-local
persistence, and live System observation. The root `dark` class switches stable
semantic tokens. Dark surfaces follow the approved deep blue-gray/slate direction
and mint primary interaction while keeping emerald success and Production-only
violet distinct. A minimal pre-bootstrap resolver duplicates the storage key and resolution rule in
`index.html` solely to protect first paint.

The completed production build increased from 242,713 to 616,010 uncompressed CSS and
JavaScript bytes (79.55 KiB to 161.48 KiB gzip). This measured growth is accepted for
the shared component and accessibility foundation. Upgrades must compare production
assets and inspect duplicate runtimes, Nuxt application chunks, and unresolved icons.
