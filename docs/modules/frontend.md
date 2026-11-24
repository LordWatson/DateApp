# Frontend (Vue 3 + Inertia 3 + TypeScript)
> Last verified against code: 2026-11-24

## Purpose
How the SPA shell is wired: entry point, layouts, shared props, route helpers, UI kit, styling, and the conventions every page follows. Feature pages themselves are documented in their module docs (mapping below).

## Key files
| Path | Role |
|---|---|
| `resources/js/app.ts` | `createInertiaApp` (title, default layout resolver, progress bar), `initializeTheme()`, `initializeFlashToast()`. No manual page resolver — `@inertiajs/vite` handles it |
| `resources/views/app.blade.php` | Root view: dark-class bootstrap script, inline `html` background colours (keep in sync with `--background` in `app.css`), Inter + Fraunces fonts (Google Fonts), `@fonts`, `@vite([app.css, app.ts, "resources/js/pages/{component}.vue"])`, `<x-inertia::head>`, `<x-inertia::app />` |
| `vite.config.ts` | Plugins: `laravel` (inputs `resources/css/app.css`, `resources/js/app.ts`; bunny font "Instrument Sans"), `inertia()`, `tailwindcss()`, `vue`, `wayfinder({ formVariants: true })` |
| `tsconfig.json` | `strict`, `moduleResolution: bundler`, alias `@/*` -> `resources/js/*` |
| `components.json` | shadcn-vue config: style `new-york-v4`, base color `neutral`, CSS variables, icon library `lucide`, aliases `@/components`, `@/components/ui`, `@/lib/utils`, `@/composables` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Root view `app`; shared props |
| `app/Http/Middleware/HandleAppearance.php` | Shares `appearance` cookie value with the Blade view |
| `config/inertia.php` | SSR + page path config |
| `resources/css/app.css` | Tailwind v4 entry, theme tokens, custom utilities and animations |
| `resources/js/layouts/` | Layouts (see below) |
| `resources/js/components/` | App components; `ui/` = shadcn-vue kit; `admin/`; `questions/` (question-type inputs) |
| `resources/js/composables/`, `lib/`, `types/` | Composables, helpers, shared TS types |
| `resources/js/actions/`, `routes/`, `wayfinder/` | **Generated** by Wayfinder; gitignored; ignored by ESLint |

## Layouts and how a page picks one
Resolution order (Inertia): a page's own `defineOptions({ layout })` wins; otherwise the `layout` callback in `app.ts` decides by component name.

| Page name | Default from `app.ts` |
|---|---|
| `Welcome`, `About` | none |
| `auth/*` | `AuthLayout` |
| `settings/*` | `[AppLayout, SettingsLayout]` (nested) |
| everything else | `AppLayout` |

| Layout | What it is | Used by |
|---|---|---|
| `layouts/MobileLayout.vue` | The real app shell: sticky glass header (logo, notifications bell with `unreadNotificationsCount`, logout), `<main>`, floating pill tab bar (sticky, ~5rem tall including its offset), `<Toaster />`. Header and `<main>` are centred at `max-w-xl`. **Main nav lives here** in `navItems`: Home, Tonight, Hub, Wins, Profile | Most feature pages set it explicitly via `defineOptions` |
| `layouts/AppLayout.vue` | Thin wrapper around `MobileLayout` (forwards `header-actions` slot) | Default for pages that set nothing (e.g. `onboarding/*`, `settings/*`) |
| `layouts/GuestLayout.vue` | Centered card with floating hearts; props `title`, `description` | via `AuthLayout` |
| `layouts/AuthLayout.vue` | Wrapper around `GuestLayout` | `auth/*`. Auth pages pass layout props with `defineOptions({ layout: { title, description } })` (or `setLayoutProps` in `TwoFactorChallenge`) |
| `layouts/settings/Layout.vue` | Settings sub-nav (Profile, Security, Appearance) | `settings/*` |
| `layouts/AdminLayout.vue` | Admin sidebar shell | `admin/*` via `defineOptions` — see `admin.md` |
| `layouts/app/AppHeaderLayout.vue`, `app/AppSidebarLayout.vue`, `auth/AuthCardLayout.vue`, `auth/AuthSplitLayout.vue` | Starter-kit leftovers, not referenced by anything. `auth/AuthSimpleLayout.vue` wraps `GuestLayout` but is also unreferenced | – |

## Shared props and types
`HandleInertiaRequests::share()` adds to every page:

| Prop | Value | Typed in `resources/js/types` |
|---|---|---|
| `name` | `config('app.name')` | `global.d.ts` `sharedPageProps` |
| `auth.user` | Full `User` model or null (hidden: `password`, `two_factor_secret`, `two_factor_recovery_codes`, `remember_token`) | `auth.ts` `User`, `Auth` |
| `sidebarOpen` | from `sidebar_state` cookie | `global.d.ts` |
| `unreadNotificationsCount` | unread `AppNotification` count (one query per request) | **not typed** — read with a cast in `MobileLayout` |
| `flash.success`, `flash.error` | session flash from `->with('success'|'error', ...)` | **not typed** — pages declare it locally |

Types: `types/auth.ts` (`User`, `Auth`, `Passkey`, `TwoFactorConfigContent`), `types/navigation.ts` (`NavItem`, `BreadcrumbItem`), `types/ui.ts` (`Appearance`, `ResolvedAppearance`, `AppVariant`, `FlashToast`), re-exported from `@/types`. Page props are typed inline in each page with `defineProps<{...}>()`.

## Calling routes from Vue
- **Wayfinder is the standard.** `@laravel/vite-plugin-wayfinder` regenerates on dev/build; never edit or commit the generated dirs.
  - Named routes: `import { dashboard, logout } from '@/routes'`, `import { store } from '@/routes/login'` -> `dashboard()` returns `{ url, method }`; `.url` for a string.
  - Controller actions: `import QuestionnaireController from '@/actions/App/Http/Controllers/QuestionnaireController'` -> `QuestionnaireController.index().url`.
  - Forms: `<Form v-bind="store.form()">` (Inertia `<Form>` + Wayfinder form variants), or `useForm` + `form.post(x().url)`.
  - `toUrl()` in `lib/utils.ts` normalises a string-or-route-object href.
- Usage in `pages/`, `components/`, `layouts/`: 32 files import `@/routes`, 9 import `@/actions`.
- **Do not use `ziggy-js`.** 17 pages (`admin/*` CRUD pages, `moments/Index`, `moments/Show`) import `route` from it, but no Ziggy config is ever provided (see Suspected issues).
- Some links are plain strings (`'/settings/profile'` in `MobileLayout`, all hrefs in `AdminLayout`).

## SSR
`config/inertia.php`: `ssr.enabled = true`, `ssr.url = http://127.0.0.1:13714`, bundle path left at default. Build with `npm run build:ssr`; output `bootstrap/ssr` is gitignored. There is no hand-written SSR entry file. Composables guard `window`/`document` access (`useAppearance.ts`) — keep new code SSR-safe. `testing.ensure_pages_exist = true`.

## Flash messages and toasts
- Toast channel: server calls `Inertia::flash('toast', ['type' => 'success'|'info'|'warning'|'error', 'message' => ...])` (used in `Settings/ProfileController`, `Settings/SecurityController`); `lib/flashToast.ts` listens to the Inertia `flash` event and calls `toast[type](message)` from `vue-sonner`.
- Banner channel: `back()->with('success', ...)` -> shared `flash.success`. Only `AdminLayout`, `pages/Partner.vue` and `pages/onboarding/Index.vue` render it.

## Composables, helpers, appearance
| File | Exports |
|---|---|
| `composables/useAppearance.ts` | `useAppearance()`, `updateTheme()`, `initializeTheme()`. Stores choice in `localStorage` + `appearance` cookie; toggles `.dark` on `<html>` |
| `composables/useCurrentUrl.ts` | `isCurrentUrl`, `isCurrentOrParentUrl` |
| `composables/useInitials.ts` | `getInitials`, `useInitials` |
| `composables/useTwoFactorAuth.ts` | 2FA setup state (see `auth-settings.md`) |
| `lib/utils.ts` | `cn()` (clsx + tailwind-merge), `toUrl()` |

Dark mode: `HandleAppearance` passes the `appearance` cookie to Blade (class `dark` on first paint, inline script handles `system`); the `appearance` and `sidebar_state` cookies are excluded from encryption in `bootstrap/app.php`. CSS variant: `@custom-variant dark (&:is(.dark *))`.

## Components and styling
- **UI kit** (`components/ui/<kit>`, import from the kit's index, e.g. `@/components/ui/button`): alert, avatar, badge, breadcrumb, button, card, checkbox, collapsible, dialog, dropdown-menu, input, input-otp, label, navigation-menu, select, separator, sheet, sidebar, skeleton, sonner, spinner, tooltip. Built on `reka-ui` + `class-variance-authority`. Excluded from Prettier and ESLint — treat as vendored.
- **App components most used**: `PrimaryButton` (props `type`, `disabled`, `loading`, `size`, `fullWidth`), `SecondaryButton`, `EmptyState` (props `emoji`, `title`, `description`, `actionLabel`; emits `action`), `InputError`, `Heading`, `PasswordInput`, `FloatingHearts`, `AnimatedBackground`, `AppLogoIcon`, `ProgressBar`, `SuccessAnimation`, `OptionCard`, `StatCard`, `ChallengeCard`, `CompatibilityCard`, `StepIndicator`; `components/questions/*` (`SingleChoice`, `MultipleChoice`, `Slider`, `Text`, `Textarea` question inputs).
- **Icons**: `@lucide/vue` (`import { Bell } from '@lucide/vue'`). Admin pages use emoji instead.
- **Tailwind v4**, CSS-first (no `tailwind.config.js`). `resources/css/app.css`: `@theme inline` maps `--color-*` to CSS variables defined in `:root` / `.dark`; custom `@utility` classes `gradient-primary`, `gradient-primary-soft`, `gradient-text`, `shadow-glow`, `card-premium`, `card-hover`, `btn-primary`, `btn-secondary`, `btn-outline`, `focus-ring`; animation classes `animate-page-fade-in`, `animate-pulse-glow`, `animate-float-heart`, `animate-success-pop`, etc. Use semantic tokens (`bg-background`, `text-foreground`, `text-muted-foreground`, `text-primary`) so dark mode works.
- **Design system** (all in `app.css`):
  - Palette: warm ivory background `#fbf7f4` (dark `#150b11`), raspberry primary `#c8245c` (dark `#e64980`), plum secondary `#632c52`. Brand gradient is `--gradient-brand` (`gradient-primary`); `--gradient-brand-text` (`gradient-text`) has a lighter dark-mode value.
  - Tailwind's `pink-*` and `purple-*` scales (and `fuchsia-100/600`) are **remapped** to the raspberry and plum ramps, so `pink-500` is not Tailwind's pink. Prefer semantic tokens in new code.
  - Type: Inter for UI (`font-sans`), Fraunces for display (`font-display`). `h1`-`h3` get `font-display` from the base layer; use `font-display` on wordmarks and big numbers.
  - Shape and depth: `--radius: 0.75rem`; `rounded-3xl` = 24px (cards), buttons are pills (`rounded-full`). `shadow-sm`...`shadow-2xl` are overridden with soft tinted shadows driven by `--shadow-color`; `shadow-glow` is for primary actions only.
  - `body` carries a fixed ambient gradient, so page and layout wrappers must not set `bg-background` (it would hide it).
  - `prefers-reduced-motion` disables animations and transitions globally.

## Page directory -> module doc
| `resources/js/pages/` | Doc |
|---|---|
| `auth/`, `settings/` | `auth-settings.md` |
| `onboarding/`, `Partner.vue` | `onboarding-partner.md` |
| `questionnaires/` | `questionnaires.md` |
| `date-night/` | `date-night.md` |
| `weekly-reflection/` | `weekly-reflection.md` |
| `achievements/` | `challenges-achievements-streaks.md` |
| `moments/`, `timeline/` | `moments-timeline.md` |
| `calendar/` | `calendar.md` |
| `love-notes/` | `love-notes.md` |
| `notifications/` | `notifications-activity-mail.md` |
| `Dashboard.vue`, `insights/`, `search/` | `dashboard-insights-search.md` |
| `intimacy/` | `intimacy.md` |
| `admin/` | `admin.md` |
| `relationship-hub/` (`RelationshipHubController`) | dashboard-insights-search.md |
| `Welcome.vue`, `About.vue` | this doc — `Route::inertia` in `routes/web.php`, no layout, no controller |

## How to add a new page
1. Controller returns `Inertia::render('<dir>/<Name>', [...props])` — page name = path under `resources/js/pages` without `.vue`; shape/format the props in the controller.
2. Create `resources/js/pages/<dir>/<Name>.vue` with `<script setup lang="ts">`, `<Head title="..." />`, inline-typed `defineProps`.
3. Layout: add `defineOptions({ layout: MobileLayout })` (project habit) or rely on the `AppLayout` default; only touch the resolver in `app.ts` for a new layout family.
4. Add the route in `routes/web.php` (inside the `auth` + `verified` + `onboarding` group for app pages), then use the regenerated Wayfinder helper from `@/routes/...` or `@/actions/...` (run `npm run dev`/`build` or `php artisan wayfinder:generate --with-form`).
5. Nav entry, if needed: `navItems` in `layouts/MobileLayout.vue`.
6. Use `components/ui/*`, `PrimaryButton`/`SecondaryButton`, `EmptyState`, theme tokens, `@lucide/vue` icons.
7. `npm run lint && npm run format && npm run types:check`; `npm run build` before running PHP feature tests that render the page.

## Commands (`package.json`)
| Command | Does |
|---|---|
| `npm run dev` / `build` / `build:ssr` | Vite dev server / client build / client + SSR build |
| `npm run lint` / `lint:check` | `eslint . --fix` / `eslint .` |
| `npm run format` / `format:check` | Prettier on `resources/` (write / check) |
| `npm run types:check` | `vue-tsc --noEmit` |

Style rules enforced: Prettier 4-space, single quotes, semicolons, Tailwind class sorting (`cn`, `clsx`, `cva`); ESLint requires `import type` for types, alphabetised import order, braces on all control statements, blank lines around control statements. `.npmrc` sets `ignore-scripts=true`.

## Rules and gotchas
- Every Inertia HTML response `@vite`-includes the page file, so a missing page or missing `public/build/manifest.json` is a 500 (also in PHPUnit).
- Generated Wayfinder dirs do not exist on a fresh clone until Vite or `wayfinder:generate` runs; `vue-tsc` fails without them.
- **Suspected issue:** `ziggy-js` `route()` is used in 17 pages with no Ziggy backend package, no `@routes` directive and no config passed -> throws "Ziggy error: missing configuration" (e.g. `resources/js/pages/moments/Index.vue:117,121`, `moments/Show.vue:36,40,52`, all `admin/*` CRUD pages).
- **Suspected issue:** most controllers flash `with('success', ...)` but `MobileLayout` does not render `flash.success`/`flash.error`; only three places do (see Flash).
- **Suspected issue:** `SavedProfileController.php:26` renders `saved-profiles/Index` and `AdminAiSettingController.php:40` renders `admin/ai-settings/Index`; neither page file exists.
- `animate-page-fade-in` (on `<main>`) uses `animation-fill-mode: backwards` on purpose: a transform left on `<main>` would make it the containing block for `position: fixed` modals in pages.
- Content inside `MobileLayout` needs about `pb-24` so the floating tab bar does not cover the last element; a page-level sticky action bar should sit at `bottom-24` (see `questionnaires/Question.vue`).
- `AnimatedBackground` paints behind the `body` background and is effectively invisible; the ambient glow comes from `body` in `app.css`.
- **Dead code:** `AppSidebar.vue` (its nav items all point at `dashboard()`), `NavMain`, `NavFooter`, `NavUser`, `AppHeader`, `AppShell`, `AppContent`, `AppSidebarHeader` are reachable only from the unused starter layouts; `LoadingSpinner`, `PlaceholderPattern`, `ProfileCard`, `QuestionCard` are imported nowhere. Edit `MobileLayout`, not `AppSidebar`, to change navigation.
- **Suspected issue:** `types/global.d.ts:18-23` `sharedPageProps` omits `unreadNotificationsCount` and `flash`.
- **Suspected issue:** `vite.config.ts:15` bundles "Instrument Sans", which no CSS references (the app uses Inter and Fraunces from Google Fonts in `app.blade.php`). `components.json:5` points at a non-existent `tailwind.config.js`.
- `auth.user` shares the whole user row (minus hidden fields) with every page.

## Related modules
`admin.md` (admin pages/layout), `auth-settings.md` (auth + settings pages, appearance page), `notifications-activity-mail.md` (bell counter), `platform.md` (middleware registration, CI, tooling).

## Tests
No JS unit/e2e test runner is configured. Frontend checks are `npm run lint:check`, `npm run format:check`, `npm run types:check` (all three run by `composer ci:check`). Pages are exercised indirectly by PHP feature tests (`php artisan test`).

## Change log
- 2026-11-24 — Initial doc.
- 2026-11-24 — Design-system overhaul: new palette, Fraunces display font, tinted shadows, pill buttons, floating tab bar, centred `max-w-xl` shell, `<Toaster />` mounted in `MobileLayout`, reduced-motion support.
