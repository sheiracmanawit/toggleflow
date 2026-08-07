# Frontend Architecture and Design System

## 1. Status

The Vue/Vite SPA, its safety rules, and the TF-24 feature-oriented source architecture
are accepted and implemented. TF-26 defines the subsequent Committed design-system
migration and consumes these established app/shared boundaries.

## 2. Product Experience

ToggleFlow should feel like a focused developer tool: precise, dependable, calm, and
fast. The interface prioritizes release state, environment boundaries, and safe
actions over decorative dashboards.

The visual centerpiece is the feature-flag view, where a project owner can understand
and change Development, Staging, and Production state without ambiguity.

## 3. Rendering Architecture Decision

The authenticated management interface is a Vue 3 single-page application. It is not
server-side rendered.

```text
Browser
  → loads the independently built Vue dashboard
    → Vue Router handles dashboard navigation
      → Vue calls Laravel JSON management endpoints
        → Laravel authenticates the session with Sanctum
```

### Why SPA

- Dashboard pages require authentication and do not need search-engine indexing.
- Project owners perform frequent interactive operations such as toggling flag state.
- Client-side routing avoids full-page reloads during project navigation.
- A first-party Vue SPA fits Sanctum's cookie-based authentication model.
- The boundary demonstrates a clear Vue frontend and Laravel application API.
- Separate application workspaces keep Vue and Laravel tooling clear while one-origin
  production routing keeps self-hosting and cookie authentication practical.

### Why not SSR

SSR would add server rendering, hydration, and deployment complexity without improving
the private dashboard's discoverability or core workflow. It may be useful later for
a separate public marketing or documentation site, but that site is not part of the
authenticated management application.

### Future public surfaces

Public surfaces can evolve independently:

```text
Public marketing site      → static generation or SSR when required
Authenticated dashboard   → Vue SPA
Public documentation       → static documentation site
Evaluation API             → versioned Laravel JSON API
```

## 4. Frontend Technology

| Concern              | Choice                                                                         |
| -------------------- | ------------------------------------------------------------------------------ |
| UI framework         | Vue 3 Composition API                                                          |
| Language             | TypeScript                                                                     |
| Build tooling        | Vite                                                                           |
| Styling              | Tailwind CSS 4 and approved Nuxt UI foundation after TF-26                     |
| Navigation           | Vue Router                                                                     |
| Shared client state  | Pinia only where state genuinely spans routes                                  |
| Server communication | A small typed HTTP client                                                      |
| Icons                | One consistent outline icon family                                             |
| Authentication       | Sanctum SPA session                                                            |
| Testing              | Vitest component tests and focused Cypress end-to-end tests for critical flows |

The original application used small ToggleFlow-owned primitives. TF-26 deliberately
supersedes the earlier restriction on a comprehensive component library by adopting
Nuxt UI in the existing Vue/Vite application. ToggleFlow retains ownership of
product-specific components, semantic tokens, accessibility, and workflow behavior;
Nuxt, SSR, and Nuxt Content are not introduced.

## 5. Information Architecture

The application uses a project-centered hierarchy:

```text
ToggleFlow
├── Sign in
├── Dashboard
├── Projects
│   ├── Project overview
│   ├── Feature flags
│   │   ├── Create flag
│   │   └── Flag details
│   ├── Environments
│   ├── API keys
│   ├── Audit log
│   └── Project settings
└── User menu
    └── Sign out
```

Routes use stable identifiers and preserve project context:

```text
/login
/app
/projects
/projects/:projectId
/projects/:projectId/flags
/projects/:projectId/flags/:flagId
/projects/:projectId/api-keys
/projects/:projectId/audit-log
/projects/:projectId/settings
```

Route guards redirect unauthenticated visitors to sign in. Server-side policies remain
authoritative; client-side route guards are not an authorization boundary.

The `/dashboard/*` URL namespace is reserved for Laravel's first-party JSON endpoints,
so Vue navigation uses `/app` for the protected dashboard landing page and must not
claim server endpoint paths.

## 6. Application Shell

Desktop uses a compact sidebar and top bar:

```text
┌──────────────────────────────────────────────────────────────┐
│ ToggleFlow        Project switcher              Search  User │
├───────────────┬──────────────────────────────────────────────┤
│ Overview      │ Checkout Service                             │
│ Feature Flags │                                              │
│ Environments  │ Page title and primary action                │
│ API Keys      │                                              │
│ Audit Log     │ Main page content                            │
│ Settings      │                                              │
└───────────────┴──────────────────────────────────────────────┘
```

- The sidebar contains project-level navigation.
- The top bar contains brand, project selection, optional search, and the user menu.
- The main region has a consistent title, description, primary action, and content
  width.
- Breadcrumbs appear only when they clarify deeper screens such as flag details.
- Mobile replaces the fixed sidebar with an accessible navigation drawer.

## 7. Visual Language

### Character

- Developer-focused
- Operational rather than promotional
- Information-dense without visual noise
- Calm during normal use
- Explicit around Production risk and destructive actions

### Color roles

Use semantic design tokens rather than raw color names inside page components.

| Role        | Direction      | Usage                                                      |
| ----------- | -------------- | ---------------------------------------------------------- |
| Brand       | Teal and mint  | Primary actions, active navigation, and focus accents      |
| Enabled     | Emerald        | Enabled flag state and healthy confirmation                |
| Disabled    | Zinc           | Normal disabled state                                      |
| Warning     | Amber          | Caution and Staging context                                |
| Danger      | Red            | Errors, revocation, archival, and destructive confirmation |
| Development | Sky            | Development environment identity                           |
| Staging     | Amber          | Staging environment identity                               |
| Production  | Violet         | Production environment identity and emphasis               |
| Surface     | White and zinc | Cards, tables, navigation, and page backgrounds            |

Disabled flags are normal configuration and must not be styled as failures. Reserve
danger colors for errors and destructive actions.

Never communicate meaning with color alone. Pair state colors with text, icons, or
patterns.

### Accepted light-theme anchors

TF-26 owns the centralized Nuxt UI and Tailwind configuration. The Nuxt UI Landing
template is a visual-character reference, but its lime palette and lime glow are not
part of ToggleFlow. Light and Dark use darker teal and brighter mint values from one
recognizable brand family.

| Token           | Value                  |
| --------------- | ---------------------- |
| Primary soft    | Teal 50 `#F0FDFA`      |
| Primary border  | Teal 200 `#99F6E4`     |
| Primary focus   | Dark teal `#0F766E`    |
| Primary action  | Dark teal `#0F766E`    |
| Primary hover   | Deeper teal `#0D655E`  |
| Primary pressed | Deepest teal `#0B554F` |
| Page background | Zinc 50 `#FAFAFA`      |
| Muted surface   | Zinc 100 `#F4F4F5`     |
| Standard border | Zinc 200 `#E4E4E7`     |
| Secondary text  | Zinc 600 `#52525B`     |
| Primary text    | Zinc 900 `#18181B`     |

Emerald remains Enabled/success, sky remains information/Development, amber remains
warning/Staging, violet remains Production, and red remains failure/destructive. The
exact shade used by each component may be adjusted during contrast verification, but
the semantic ownership must not be redefined per page.

### Typography

- Use Instrument Sans with system sans-serif fallbacks for application text.
- Use IBM Plex Mono with system monospace fallbacks for flag keys, API-key prefixes,
  code, and request examples.
- Prefer a restrained type scale with strong hierarchy rather than oversized headings.
- Use tabular numbers where rapidly changing counts or timestamps benefit from stable
  alignment.

### Spacing and surfaces

- Use a consistent spacing scale from Tailwind tokens.
- Prefer borders and subtle surface changes over heavy shadows.
- Use cards only when they establish a meaningful group.
- Keep tables compact enough for operational scanning.
- Preserve generous separation around irreversible or Production-sensitive controls.

## 8. Tailwind Strategy

Tailwind utilities should implement a small semantic component system. Pages compose
components and should not repeat long, inconsistent class lists.

Use CSS custom properties or an equivalent token layer for semantic values:

```css
:root {
    --color-brand: #4f46e5;
    --color-enabled: /* semantic emerald token */;
    --color-warning: /* semantic amber token */;
    --color-danger: /* semantic red token */;
    --color-surface: /* semantic zinc/white token */;
}
```

Configure complete shade scales and derived Nuxt UI semantic tokens during TF-26 and
test both supported presentations for accessible contrast. Avoid scattering
arbitrary values across Vue templates.

### Component variants

Prefer explicit semantic variants:

```vue
<AppButton variant="primary">Create flag</AppButton>
<AppButton variant="secondary">Cancel</AppButton>
<AppButton variant="danger">Revoke key</AppButton>
<AppBadge variant="enabled">Enabled</AppBadge>
<AppBadge variant="disabled">Disabled</AppBadge>
```

Use a class composition helper only if it materially improves variant safety. Do not
introduce a styling abstraction before repeated patterns exist.

### Theme preference

TF-26 supports Light, Dark, and System. The app-owned theme preference capability
stores the closed preference string in browser-local storage under
`toggleflow.theme-preference`; it never stores user, project, or server data. System
is the default and responds to `prefers-color-scheme` changes. A minimal script in
`index.html` applies the same validated preference and resolution rule before the
application entry to prevent an opposite-theme first-paint flash.

The resolved presentation is expressed by the root `dark` class and `color-scheme`.
Semantic token names and product meaning remain stable. The dark presentation uses
deep blue-gray structural surfaces derived from `#2E3047`, elevated slate surfaces,
cool neutral text, and mint primary interaction derived from `#3BBA9C`. Success and
Enabled remain a separate emerald semantic treatment.

## 9. Feature-oriented frontend architecture

TF-24 replaced the layer-first source organization with three top-level ownership
areas before TF-26 and TF-27 implementation:

```text
apps/dashboard/src/
├── app/       # bootstrap, router, providers, layouts, global styles, composition
├── features/  # authentication, projects, flags, credentials, audit, dashboard
└── shared/    # product-agnostic API, UI, composables, types, and utilities
```

Current feature owners are `authentication`, `projects`, `feature-flags`,
`credentials`, `audit-history`, and `dashboard`. Each exposes intentional consumers
through `features/<owner>/index.ts`. Project overview is app-owned because it composes
projects and feature flags. Matching `@app`, `@features`, and `@shared` aliases are
configured in TypeScript, Vite, and Vitest.

### Responsibilities

- Code used by one component stays with that component.
- Code reused within one workflow stays in its owning feature.
- A composable owns a cohesive reactive workflow when extracting it makes request,
  mutation, cancellation, confirmation, or accessibility behavior independently
  understandable and testable; do not create pass-through composables that merely
  rename page-local refs.
- Cross-feature consumption uses an intentional public feature entry point.
- Shared contains only domain-neutral capabilities and never depends on a feature.
- App composes features and shared foundations.
- Feature dependencies remain acyclic.
- Pinia holds only state that genuinely spans routes or distant workflows.

Use Nuxt UI primitives directly when their semantics fit. Create a ToggleFlow
component only when it adds stable product meaning, behavior, safety, or composition.
Do not wrap every Nuxt UI primitive or depend on undocumented library internals.

TF-26 places Nuxt UI registration, `UApp`, AppConfig, router wiring, and global CSS
in `app`. Domain-neutral presentation foundations may live in `shared/ui`; product
compositions remain feature-owned. Light uses darker teal primary and Dark uses
brighter mint primary from the same family. Both retain Production-only violet,
success/Enabled emerald, information/Development sky, warning/Staging amber,
destructive/failure red, and neutral/Disabled semantics. Labels and accessible names
remain required because color is never the sole state signal. The development-only
`/__ui-foundation` fixture includes the preference selector and is absent from
production registration and navigation.

Avoid a single global store containing all projects, flags, loading states, and form
errors.

`pnpm lint` runs both ESLint and the frontend boundary checker. It rejects shared-to-
feature imports, feature-to-app imports, deep cross-feature imports, and cyclic
feature dependencies. See [ADR 004](../decisions/004-feature-oriented-vue-architecture.md)
for the complete ownership inventory and dependency matrix.

## 10. Core Screen Specifications

### Sign in

- Show a focused email and password form.
- Use a narrow readable width and a clear primary action.
- Provide inline validation and a non-sensitive invalid-credentials message.
- Keep local demo credentials in developer documentation; never retrieve or display
  account passwords through the dashboard interface.
- Redirect authenticated users away from the sign-in page.

### Dashboard

Prioritize actionable status over decorative charts:

- Project count
- Active flag count
- Production-enabled flag count
- Recent management activity
- Project summaries

Do not show evaluation analytics or invented health data before supporting product
behavior and data exist.

### Project list and creation

- Explain that a project represents one consuming application or service.
- Show project name, description, lifecycle status, flag count, and recent update.
- Explain that Development, Staging, and Production are created automatically.
- Provide an empty state leading directly to project creation.

### Project overview

Show environment identity and the most important flag state:

```text
Checkout Service

12 flags   8 enabled in Production   Updated 10 minutes ago

┌──────────────────────┬─────────────┬─────────┬────────────┐
│ Flag                 │ Development │ Staging │ Production │
├──────────────────────┼─────────────┼─────────┼────────────┤
│ New checkout         │ Enabled     │ Enabled │ Disabled   │
│ Recommendations      │ Enabled     │ Disabled│ Disabled   │
└──────────────────────┴─────────────┴─────────┴────────────┘
```

### Feature-flag list

Each row shows:

- Human-readable name
- Stable monospace key
- Short description when space permits
- Development, Staging, and Production state
- Last changed time
- Contextual actions

Desktop uses a table for comparison. Mobile uses stacked cards so environment states
remain legible.

### Flag creation

- Explain the difference between the display name and immutable flag key.
- Generate a suggested key from the name while allowing correction before creation.
- State clearly that a new flag begins disabled in every environment.
- Prevent duplicate keys within the project.

### Flag details

- Keep the flag name, key, description, and lifecycle visible.
- Present one state control per environment.
- Show recent changes relevant to the flag.
- Make Production visually distinct without making it permanently alarming.
- Require explicit confirmation before enabling or disabling Production.

Production confirmation must state the expected impact:

```text
Enable “new-checkout” in Production?

Applications using this Production environment key will begin receiving true.
This change does not deploy application code.
```

### API keys

- Group keys by environment.
- Show name, safe prefix, creation time, last-used time when available, and state.
- Show the complete key exactly once after creation.
- Provide a copy action and environment-variable example.
- Require the user to acknowledge that the secret cannot be displayed again.
- Confirm revocation and explain that applications using the key will lose access.

### Audit log

- Display newest events first.
- Identify actor, action, subject, project or environment context, and time.
- Use human-readable action text while preserving stable action identifiers in data.
- Do not mix high-volume evaluation events into this view.
- Filtering is optional after the basic readable history works.

## 11. State and Data Handling

Use explicit states for every route-level request:

```text
idle → loading → success
               ↘ empty
               ↘ error
```

- Use skeletons for initial page loading where they preserve layout.
- Use inline errors for validation or failures requiring user action.
- Use toasts for brief confirmation after successful background actions.
- Do not use a toast as the only place to communicate a critical failure.
- Preserve confirmed server state after a failed mutation.
- Use optimistic toggles only if rollback is immediate, reliable, and visually clear.
- Disable duplicate submission while a command is in flight.
- Treat server responses as authoritative after mutations.

## 12. Accessibility Requirements

- All functionality is usable with a keyboard.
- Visible focus indicators remain present on interactive controls.
- Form inputs have persistent labels and associated error messages.
- Dialogs move focus inside, trap it appropriately, and return focus on close.
- Toggle state is exposed through accessible names and state attributes.
- Color is never the only indication of flag or environment state.
- Text and controls meet the project's chosen WCAG contrast target.
- Tables have meaningful headers and an equivalent readable mobile representation.
- Loading and mutation results are announced appropriately to assistive technology.
- Motion respects reduced-motion preferences.

## 13. Responsive Behavior

### Desktop

- Fixed or sticky project navigation
- Dense comparison tables
- Page-level actions aligned with titles
- Side-by-side summary cards where useful

### Tablet

- Collapsible navigation
- Reduced table columns with the essential environments preserved
- Forms remain single-column unless a pair of fields has a clear relationship

### Mobile

- Navigation uses a drawer or sheet.
- Feature rows become cards with explicit environment labels.
- Primary actions remain easy to reach without covering content.
- Dialogs use most of the viewport while retaining safe margins.
- API keys and flag keys wrap or scroll without breaking layout.

## 14. UX Safety Rules

- Treat Disabled as a normal state, not an error.
- Distinguish the three environments in text as well as color.
- Require confirmation for Production state changes, key revocation, and archival.
- State the impact of a Production change in the confirmation dialog.
- Do not hide destructive actions beside common navigation actions without separation.
- Never display a complete API key after its creation flow.
- Keep immutable flag keys easy to copy.
- Explain empty states and offer one clear next action.
- Do not fabricate metrics that the backend does not calculate reliably.

## 15. Available Experience and Committed Redesign Boundary

### Available workflows that must remain complete

1. Sign in
2. Dashboard
3. Project list and creation
4. Project overview
5. Feature-flag list, creation, and details
6. API-key creation and revocation
7. Audit history
8. Loading, empty, validation, success, and failure states for the primary flow

### Committed

- TF-24 feature-oriented architecture
- TF-26 Nuxt UI and centralized teal/mint-and-zinc ToggleFlow themes
- TF-27 workflow-based dashboard redesign and cross-application verification

### Outside the committed redesign

- Advanced analytics and charts
- Command palette
- Visual rollout-rule builder
- Drag-and-drop prioritization
- Organization and membership screens
- User-selectable or tenant-specific custom themes
- Elaborate animation
- Dedicated SSR marketing site

User-defined palettes, per-project themes, and account-synchronized preferences are
outside the committed redesign.

## 16. Frontend Definition of Done

A frontend story is complete when:

- The intended user outcome works against real backend behavior.
- Loading, empty, validation, success, and error states are handled where applicable.
- Server authorization failures are handled safely.
- Keyboard interaction and visible focus have been verified.
- Responsive behavior has been checked at desktop and mobile widths.
- State is not communicated through color alone.
- Reusable behavior is factored into an appropriate primitive, domain component,
  composable, or service without premature abstraction.
- Critical interaction tests pass.
- No placeholder screen or dead navigation appears in the demonstrated workflow.

## 17. Related Documentation

- [Product Requirements](../05-product-requirements.md)
- [Domain and Architecture](overview.md)
- [Architecture and Flow Diagrams](system-diagrams.md)
- [Authentication and API Key Decision](authentication-and-api-keys.md)
