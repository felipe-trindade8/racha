---
name: vitest-frontend
description: Write and run frontend unit/component tests for the Nuxt 4 app with Vitest and @nuxt/test-utils. Use when testing composables (useXxx), critical components, or service modules in the frontend, per docs/coding-standards.md.
---

# Vitest Frontend Testing

Testing standard for the Nuxt 4 frontend. Per `docs/coding-standards.md`: use **Vitest**,
test **composables** and **critical components**. This skill keeps that setup consistent
and runnable inside Docker (no Node required on the host).

## Stack

- **Vitest** — test runner.
- **@nuxt/test-utils** — Nuxt-aware environment + helpers (`mountSuspended`, `mockNuxtImport`, `registerEndpoint`).
- **@vue/test-utils** — Vue component mounting.
- **happy-dom** — DOM environment (lighter than jsdom).

## One-time setup (not yet installed)

If `vitest` is missing from `frontend/package.json`, install **inside the container** so the
bind-mounted manifest/lockfile and the container `node_modules` volume stay in sync:

```bash
docker compose exec -T frontend npm install -D vitest @nuxt/test-utils @vue/test-utils happy-dom --save
```

Add a script to `frontend/package.json`:

```json
"scripts": {
  "test": "vitest run",
  "test:watch": "vitest"
}
```

Create `frontend/vitest.config.ts`:

```ts
import { defineVitestConfig } from '@nuxt/test-utils/config'

export default defineVitestConfig({
  test: {
    environment: 'nuxt', // gives auto-imports, #app, #imports, runtimeConfig
  },
})
```

## Where tests go

Co-locate next to the unit under test, suffixed `.spec.ts` (or `.nuxt.spec.ts` when the test
needs the Nuxt environment). Mirror the `app/` layout — composables next to composables, etc.

## Testing a composable (`useXxx`)

```ts
// app/composables/useAttendance.nuxt.spec.ts
import { describe, it, expect } from 'vitest'
import { useAttendance } from './useAttendance'

describe('useAttendance', () => {
  it('marks a player available', () => {
    const { status, setStatus } = useAttendance()
    setStatus('available')
    expect(status.value).toBe('available')
  })
})
```

## Testing a component

```ts
// app/components/PlayerCard.nuxt.spec.ts
import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import PlayerCard from './PlayerCard.vue'

describe('PlayerCard', () => {
  it('renders the nickname', async () => {
    const wrapper = await mountSuspended(PlayerCard, {
      props: { player: { id: 1, name: 'Ana', nickname: 'Ana10', rating: 4 } },
    })
    expect(wrapper.text()).toContain('Ana10')
  })
})
```

## Mocking the backend

Business logic lives in composables; API calls live in `XxxService`. Mock at the boundary.

- Stub an endpoint the SSR/`$fetch` layer hits:

  ```ts
  import { registerEndpoint } from '@nuxt/test-utils/runtime'

  registerEndpoint('/api/v1/players', () => ({
    data: [{ id: 1, nickname: 'Ana10' }], // API uses snake_case keys
  }))
  ```

- Replace an auto-imported composable/util:

  ```ts
  import { mockNuxtImport } from '@nuxt/test-utils/runtime'

  mockNuxtImport('useRuntimeConfig', () => () => ({
    public: { apiBase: 'http://localhost:8000/api/v1' },
  }))
  ```

## Conventions

- API payloads use **snake_case** keys (`player_id`); frontend code/vars use **camelCase** —
  assert on the shape each side actually produces.
- Test **behavior and business rules**, not framework internals or Nuxt UI components.
- Keep one logical concern per `it`; name tests after the rule they protect.

## Running

```bash
docker compose exec -T frontend npm run test          # CI-style, one shot
docker compose exec -T frontend npm run test:watch    # local TDD
docker compose exec -T frontend npx vitest run path/to/file.spec.ts  # single file
```
