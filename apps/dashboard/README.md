# ToggleFlow dashboard

The dashboard is a Vue 3/Vite SPA. Nuxt UI 4.10.0 supplies presentation and
interaction primitives; it does not introduce Nuxt routing, SSR, or server behavior.

## UI foundation ownership

- `src/app/app.config.ts` owns Nuxt UI color aliases, icons, and defaults.
- `src/app/app.css` imports Tailwind and Nuxt UI once and owns semantic tokens.
- `src/app/App.vue` owns the root `UApp` provider.
- `src/app/theme` owns the closed Light/Dark/System preference, local persistence,
  System media observation, and root class/color-scheme application.
- `src/app/components/ThemePreferenceSelector.vue` owns the accessible global
  three-choice control.
- `src/shared/ui` contains only genuinely domain-neutral presentation foundations.
- Product compositions stay with their feature owner.

Use Nuxt UI primitives directly when documented behavior fits. Add a product
component only when it encodes stable product semantics or safety. Do not add
one-for-one wrappers, raw page palette values, a second reset, or another component
framework.

Primary interaction uses one teal/mint family: accessible darker teal in Light and
brighter palette-4 mint in Dark. Keep violet exclusive to Production identity and
emerald exclusive to success/Enabled; each state still requires a text or icon cue.

## Adding or customizing a component

1. Review its documented props, slots, keyboard behavior, and dismissal behavior for
   Nuxt UI 4.10.0.
2. Use semantic colors and tokens. Disabled is neutral; danger is for failure and
   destructive actions. Pair color with text or an accessible name.
3. Put global defaults in AppConfig, ToggleFlow-only values in `app.css`, reusable
   domain-neutral composition in `shared/ui`, or product behavior in its feature.
4. Test labels, validation association, focus entry/restoration, Escape, reduced
   motion, and responsive behavior as applicable.
5. Inspect `/__ui-foundation` during `pnpm dev` at mobile, tablet, and desktop widths.
   The route is not registered in production.

## Safe upgrades and troubleshooting

Read release notes and migration guidance, update exact versions through pnpm, and
review package provenance. Run every frontend gate and compare production CSS and
JavaScript sizes. Inspect duplicate Vue/Tailwind runtimes, Nuxt application chunks,
CSS warnings, and unresolved icons.

If components are unresolved, reinstall with the pinned pnpm and run Vite to refresh
`auto-imports.d.ts` and `components.d.ts`. If styling is absent, confirm Tailwind and
Nuxt UI are imported once in `src/app/app.css` and Vite registers only
`@nuxt/ui/vite`, not a second Tailwind transform plugin.

Theme preference uses the fixed `toggleflow.theme-preference` key and stores only
`light`, `dark`, or `system`. System is the default. Keep the small resolver in
`index.html` behaviorally aligned with `src/app/theme/themePreference.ts`; it is
intentionally duplicated so the resolved root class is present before first paint.
New theme-specific values belong in the centralized semantic variables, never in
conditional component templates or browser storage beyond the closed preference.
