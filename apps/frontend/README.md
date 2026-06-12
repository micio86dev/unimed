# UniMed — Frontend

Nuxt 4 SPA (Vue 3 + TypeScript) for the UniMed admission-test platform.

- **State**: Pinia (`app/stores`) · **Styling**: Tailwind CSS 4 + shadcn-vue primitives (`app/components/ui`)
- **Charts**: hand-built SVG components (`app/components/charts`)
- **Auth**: token-based against the Laravel API (`app/composables/useApi.ts`)

## Develop

```bash
pnpm install            # from the repo root
pnpm --filter @unimed/frontend dev
```

Set `NUXT_PUBLIC_API_BASE` (defaults to `http://localhost:8000/api`).

## Scripts

| Command          | Description              |
| ---------------- | ------------------------ |
| `pnpm dev`       | Dev server               |
| `pnpm build`     | Production build         |
| `pnpm typecheck` | `vue-tsc` type-check     |
| `pnpm test`      | Vitest unit tests        |
| `pnpm test:e2e`  | Playwright E2E           |

See the [root README](../../README.md) and [docs](../../docs) for the full picture.
